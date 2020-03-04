<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\NullShopDomain;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;

/**
 * Response for ensuring an authenticated request.
 */
class AuthShopify
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

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
     * @return self
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
        // Check the HMAC, if present
        $this->verifyHmac($request);

        if ($this->shopSession->guest()) {
            // Login the shop and verify their data
            return $this->loginShop($request, $next);
        }

        // Verify shop data is good by comparison
        return $this->verifyShop($request, $next);
    }

    /**
     * Verify HMAC data, if present.
     *
     * @param Request $request The request object.
     *
     * @return void
     */
    private function verifyHmac(Request $request): void
    {
        $hmac = $this->getHmac($request);
        if ($hmac === null) {
            // No HMAC, move on...
            return;
        }

        // We have HMAC, validate it
        $data = $this->getData($request, $hmac[1]);
        if (!$this->apiHelper->verifyRequest($data)) {
            // Something didn't match
            throw new SignatureVerificationException('Unable to verify signature.');
        }
    }

    /**
     * Login and verify the shop and it's data.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    private function loginShop(Request $request, Closure $next)
    {
        // Grab the domain
        $shopDomain = $this->getShopDomainFromData($request);
        if ($shopDomain->isNull()) {
            // No shop... we have no way of knowing who this is... go to login
            return Redirect::route('login');
        }

        // Log the shop in
        $this->shopSession->make($shopDomain);
        if (!$this->shopSession->isValid()) {
            // Somethings not right... missing token?
            return Redirect::route(
                'authenticate.oauth',
                ['shop' => $shopDomain->toNative()]
            );
        }

        return $next($request);
    }

    /**
     * Verify the shop is alright, if theres a current session, it will compare.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return void
     */
    private function verifyShop(Request $request, Closure $next)
    {
        // Grab the domain
        $shopDomain = $this->getShopDomainFromData($request);
        if (!$shopDomain->isNull()) {
            if (!$this->shopSession->isValidCompare($shopDomain)) {
                // Mis-match of shops
                return Redirect::route(
                    'authenticate.oauth',
                    ['shop' => $shopDomain->toNative()]
                );
            }
        }

        return $next($request);
    }

    /**
     * Grab the HMAC value, if present, and how it was found.
     * Order of precedence is:
     *
     *  - GET/POST Variable
     *  - Headers
     *  - Referer
     *
     * @param Request $request The request object.
     *
     * @return null|array
     */
    private function getHmac(Request $request): ?array
    {
        // All possible methods
        $options = [
            // GET/POST
            DataSource::INPUT()->toNative() => $request->input('hmac'),
            // Headers
            DataSource::HEADER()->toNative() => $request->header('X-Shop-Signature'),
            // Headers: Referer
            DataSource::REFERER()->toNative() => function () use ($request): ?string {
                $url = parse_url($request->header('referer'), PHP_URL_QUERY);
                parse_str($url, $refererQueryParams);
                if (!$refererQueryParams || !isset($refererQueryParams['hmac'])) {
                    return null;
                }

                return $refererQueryParams['hmac'];
            }
        ];

        // Loop through each until we find the HMAC
        foreach ($options as $method => $value) {
            $result = is_callable($value) ? $value() : $value;
            if ($result !== null) {
                return [$result, $method];
            }
        }

        return null;
    }


    /**
     * Grab the data.
     *
     * @param Request $request The request object.
     * @param string  $source  The source of the data.
     *
     * @return array
     */
    private function getData(Request $request, string $source): array
    {
        // All possible methods
        $options = [
            // GET/POST
            DataSource::INPUT()->toNative() => function () use ($request): array {
                // Verify
                $verify = [];
                foreach ($request->all() as $key => $value) {
                    $verify[$key] = is_array($value) ? '["'.implode('", "', $value).'"]' : $value;
                }

                return $verify;
            },
            // Headers
            DataSource::HEADER()->toNative() => function () use ($request): array {
                // Always present
                $shop = $request->header('X-Shop-Domain');
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

                return $verify;
            },
            // Headers: Referer
            DataSource::REFERER()->toNative() => function () use ($request): array {
                $url = parse_url($request->header('referer'), PHP_URL_QUERY);
                parse_str($url, $refererQueryParams);

                // Verify
                $verify = [];
                foreach ($refererQueryParams as $key => $value) {
                    $verify[$key] = is_array($value) ? '["'.implode('", "', $value).'"]' : $value;
                }

                return $verify;
            }
        ];

        return $options[$source]();
    }

    /**
     * Gets the shop domain from the data.
     *
     * @param Request $request The request object.
     *
     * @return ShopDomainValue
     */
    private function getShopDomainFromData(Request $request): ShopDomainValue
    {
        $options = [
            DataSource::INPUT()->toNative(),
            DataSource::HEADER()->toNative(),
            DataSource::REFERER()->toNative()
        ];
        foreach ($options as $option) {
            $result = $this->getData($request, $option);
            if ($result && isset($result['shop'])) {
                return new ShopDomain($result['shop']);
            }
        }

        return new NullShopDomain();
    }
}
