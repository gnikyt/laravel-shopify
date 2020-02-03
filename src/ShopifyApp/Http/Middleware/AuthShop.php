<?php

namespace OhMyBrew\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Exceptions\MissingShopDomainException;
use OhMyBrew\ShopifyApp\Exceptions\SignatureVerificationException;
use OhMyBrew\ShopifyApp\Objects\Values\NullableShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * Response for ensuring an authenticated shop.
 */
class AuthShop
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Shop session service.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Constructor.
     *
     * @param IApiHelper  $apiHelper   The API helper.
     * @param IShopQuery  $shopQuery   The querier for shops.
     * @param ShopSession $shopSession Shop session service.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopQuery $shopQuery, ShopSession $shopSession)
    {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
        $this->shopSession = $shopSession;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $validation = $this->validateShop($request);
        if ($validation !== true) {
            return $validation;
        }

        return $next($request);
    }

    /**
     * Checks we have a valid shop.
     *
     * @param Request $request The request object.
     *
     * @return bool|RedirectResponse
     */
    protected function validateShop(Request $request)
    {
        // Grab the shop's myshopify domain from query or session
        $shopDomain = $this->getShopDomainFromRequest($request);

        // Get the shop based on domain and update the session service
        $shop = $this->shopQuery->getByDomain($shopDomain, [], true);
        $this->shopSession->setShop($shop);

        // We need to do a full flow if no shop or it is deleted
        if ($shop === null || $shop->trashed() || !$this->shopSession->isValid()) {
            // We have a bad session
            return $this->handleBadSession(
                $request,
                new NullableShopDomain($shopDomain)
            );
        }

        // Everything is fine!
        return true;
    }

    /**
     * Grab the shop's myshopify domain from query, referer, headers
     * or session.
     *
     * Getting the domain for the shop from session is unreliable
     * because if 2 shops have the same app open in the same browser
     * (e.g. someone is managing the same app on 2 stores at the same
     * time) then the sessions can bleed into each other due to the
     * cookies being run on the same domain (the domain of the app,
     * not the individual shops admin dashboard).
     *
     * To get around this select a domain based on other information
     * available, and make sure to verify the input before use. This is
     * still not 100% reliable.
     *
     * Order of precedence is:
     *
     *  - GET/POST Variable
     *  - Referer
     *  - Headers
     *  - Session
     *
     * @param Request $request The request object.
     *
     * @throws MissingShopDomainException
     *
     * @return ShopDomain
     */
    private function getShopDomainFromRequest(Request $request): ShopDomain
    {
        // Query variable is highest priority
        $shopDomainParam = $this->getQueryDomain($request);
        if ($shopDomainParam) {
            return new ShopDomain($shopDomainParam);
        }

        // Then the value in the referer header (if validated)
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopRefererParam = $this->getRefererDomain($request);
        if ($shopRefererParam) {
            return new ShopDomain($shopRefererParam);
        }

        // Grab the shop's myshopify domain from headers
        // Referer is more reliable
        // For SPA's we need X-Shop-Domain and verification headers
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopHeaderParam = $this->getHeaderDomain($request);
        if ($shopHeaderParam) {
            return new ShopDomain($shopHeaderParam);
        }

        // If none of the above are available then pull from the session
        $shopDomainSession = $this->session->getDomain();
        if (!$shopDomainSession->isNull()) {
            return new ShopDomain($shopDomainSession->toNative());
        }

        // No domain :(
        throw new MissingShopDomainException('Unable to get shop domain.');
    }

    /**
     * Get the query variable shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param Request $request The request object.
     *
     * @throws SignatureVerificationException
     *
     * @return string|null
     */
    private function getQueryDomain(Request $request): ?string
    {
        // Extract the referer
        $shop = $request->input('shop');
        if (!$shop) {
            return null;
        }

        // Verify
        $verify = [];
        foreach ($request->all() as $key => $value) {
            $verify[$key] = is_array($value) ? '["'.implode('", "', $value).'"]' : $value;
        }

        // Make sure there is no param spoofing attempt
        if ($this->apiHelper->verifyRequest($verify)) {
            return $shop;
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Get the referer shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param Request $request The request object.
     *
     * @throws SignatureVerificationException
     *
     * @return string|null
     */
    private function getRefererDomain(Request $request): ?string
    {
        // Extract the referer
        $referer = $request->header('referer');
        if (!$referer) {
            return null;
        }

        // Get the values of the referer query params as an array
        $url = parse_url($referer, PHP_URL_QUERY);
        parse_str($url, $refererQueryParams);
        if (!$refererQueryParams) {
            return null;
        }

        // These 3 must always be present
        if (!isset($refererQueryParams['shop']) || !isset($refererQueryParams['hmac']) || !isset($refererQueryParams['timestamp'])) {
            return null;
        }

        // Verify
        $verify = [];
        foreach ($refererQueryParams as $key => $value) {
            $verify[$key] = is_array($value) ? '["'.implode('", "', $value).'"]' : $value;
        }

        // Make sure there is no param spoofing attempt
        if ($this->apiHelper->verifyRequest($verify)) {
            return $refererQueryParams['shop'];
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Get the header shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param Request $request The request object.
     *
     * @throws SignatureVerificationException
     *
     * @return string|null
     */
    private function getHeaderDomain(Request $request): ?string
    {
        // Extract the referer
        $shop = $request->header('X-Shop-Domain');
        if (!$shop) {
            return null;
        }

        // Always present
        $signature = $request->header('X-Shop-Signature');
        $timestamp = $request->header('X-Shop-Time');

        $verify = [
            'shop'      => $shop,
            'hmac'      => $signature,
            'timestamp' => $timestamp,
        ];

        // Sometimes present
        $code = $request->header('X-Shop-Code') ?? null;
        $locale = $request->header('X-Shop-Locale') ?? null;
        $state = $request->header('X-Shop-State') ?? null;
        $id = $request->header('X-Shop-ID') ?? null;
        $ids = $request->header('X-Shop-IDs') ?? null;

        foreach (compact('code', 'locale', 'state', 'id', 'ids') as $key => $value) {
            if ($value) {
                $verify[$key] = is_array($value) ? '["'.implode('", "', $value).'"]' : $value;
            }
        }

        // Make sure there is no param spoofing attempt
        if ($this->apiHelper->verifyRequest($verify)) {
            return $shop;
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Handles a bad shop session.
     *
     * @param Request            $request    The request object.
     * @param NullableShopDomain $shopDomain The incoming shop domain.
     *
     * @return RedirectResponse
     */
    protected function handleBadSession(
        Request $request,
        NullableShopDomain $shopDomain
    ): RedirectResponse {
        // Clear all session variables (domain, token, user, etc)
        $this->shopSession->forget();

        // Set the return-to path so we can redirect after successful authentication
        Session::put('return_to', $request->fullUrl());

        // Depending on the type of grant mode, we need to do a full auth or partial
        return Redirect::route(
            'authenticate',
            array_merge(
                $request->all(),
                ['shop' => $shopDomain]
            )
        );
    }
}
