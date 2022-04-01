<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Exceptions\MissingShopDomainException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use Osiset\ShopifyApp\Objects\Values\NullShopDomain;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Util;
use Osiset\ShopifyApp\Traits\VerifyShopifyMiddleware;


/**
 * Response for ensuring an authenticated request.
 */
class VerifyShopifyExternal extends VerifyShopify
{

    /**
     * The shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Constructor.
     *
     * @param IApiHelper  $apiHelper   The API helper.
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return void
     */
    public function __construct(IApiHelper $apiHelper, ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
        $this->apiHelper = $apiHelper;
        $this->apiHelper->make();
    }

    /**
     * Handle an incoming request.
     * If HMAC is present, it will try to valiate it.
     * If shop is not logged in, redirect to authenticate will happen.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @throws SignatureVerificationException
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the domain and check the HMAC (if present)
        $domain = $this->getShopDomainFromRequest($request);

        $checks = [];
        if ($this->shopSession->guest()) {

            // Login the shop and verify their data
            $checks[] = 'loginShop';
        }

        // Verify the Shopify session token and verify the shop data
        array_push($checks, 'verifyShopifySessionToken', 'verifyShop');

        // Loop all checks needing to be done, if we get a false, handle it
        foreach ($checks as $check) {
            $result = call_user_func([$this, $check], $request, $domain);
            if ($result === false) {
                return $this->handleBadVerification($request, $domain);
            }
        }

        return $next($request);
    }

    /**
     * Login and verify the shop and it's data.
     *
     * @param Request         $request The request object.
     * @param ShopDomainValue $domain  The shop domain.
     *
     * @return bool
     */
    private function loginShop(Request $request, ShopDomainValue $domain): bool
    {
        // Log the shop in
        $status = $this->shopSession->make($domain);
        if (! $status || ! $this->shopSession->isValid()) {
            // Somethings not right... missing token?
            return false;
        }

        return true;
    }

    /**
     * Verify the shop is alright, if theres a current session, it will compare.
     *
     * @param Request         $request The request object.
     * @param ShopDomainValue $domain  The shop domain.
     *
     * @return bool
     */
    private function verifyShop(Request $request, ShopDomainValue $domain): bool
    {
        // Grab the domain
        if (! $domain->isNull() && ! $this->shopSession->isValidCompare($domain)) {
            // Somethings not right with the validation
            return false;
        }

        return true;
    }

    /**
     * Check the Shopify session token.
     *
     * @param Request         $request The request object.
     * @param ShopDomainValue $domain  The shop domain.
     *
     * @return bool
     */
    private function verifyShopifySessionToken(Request $request, ShopDomainValue $domain): bool
    {
        // Ensure Shopify session token is OK
        $incomingToken = $request->query('session');
        if ($incomingToken) {
            if (! $this->shopSession->isSessionTokenValid($incomingToken)) {
                // Tokens do not match
                return false;
            }

            // Save the session token
            $this->shopSession->setSessionToken($incomingToken);
        }

        return true;
    }

    /**
     * Grab the shop, if present, and how it was found.
     * Order of precedence is:.
     *
     *  - GET/POST Variable
     *  - Headers
     *  - Referer
     *
     * @param Request $request The request object.
     *
     * @return ShopDomainValue
     */
    private function getShopDomainFromRequest(Request $request): ShopDomainValue
    {
        // All possible methods
        $options = [
            // GET/POST
            DataSource::INPUT()->toNative() => $request->input('shop'),
            // Headers
            DataSource::HEADER()->toNative() => $request->header('X-Shop-Domain'),
            // Headers: Referer
            DataSource::REFERER()->toNative() => function () use ($request): ?string {
                $url = parse_url($request->header('referer'), PHP_URL_QUERY);
                parse_str($url, $refererQueryParams);
                if (! $refererQueryParams || ! isset($refererQueryParams['shop'])) {
                    return null;
                }

                return $refererQueryParams['shop'];
            },
        ];

        // Loop through each until we find the HMAC
        foreach ($options as $method => $value) {
            $result = is_callable($value) ? $value() : $value;
            if ($result !== null) {
                // Found a shop
                return ShopDomain::fromNative($result);
            }
        }

        // No shop domain found in any source
        return NullShopDomain::fromNative(null);
    }


    /**
     * Handle bad verification by killing the session and redirecting to auth.
     *
     * @param Request         $request The request object.
     * @param ShopDomainValue $domain  The shop domain.
     *
     * @throws MissingShopDomainException
     *
     * @return RedirectResponse
     */
    private function handleBadVerification(Request $request, ShopDomainValue $domain)
    {
        if ($domain->isNull()) {
            // We have no idea of knowing who this is, this should not happen
            throw new MissingShopDomainException();
        }

        // Set the return-to path so we can redirect after successful authentication
        Session::put('return_to', $request->fullUrl());

        // Kill off anything to do with the session
        $this->shopSession->forget();

        // Mis-match of shops
        return Redirect::route(
            Util::getShopifyConfig('route_names.authenticate'),
            ['shop' => $domain->toNative()]
        );
    }

}
