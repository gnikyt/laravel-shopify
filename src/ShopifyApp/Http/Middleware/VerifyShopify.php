<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Assert\AssertionFailedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Exceptions\HttpException;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use function Osiset\ShopifyApp\getShopifyConfig;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\SessionToken;
use Osiset\ShopifyApp\Objects\Values\NullShopDomain;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;

class VerifyShopify
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Constructor.
     *
     * @param IApiHelper $apiHelper The API helper.
     *
     * @return void
     */
    public function __construct(IApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
        $this->apiHelper->make();
    }

    public function handle(Request $request, Closure $next)
    {
        // Verify the HMAC (if available)
        $this->verifyHmac($request);

        // Get the token (if available)
        $tokenSource = $request->ajax() ? $request->bearerToken() : $request->all('token');
        if (empty($tokenSource)) {
            if ($request->ajax()) {
                // AJAX, return HTTP exception
                throw new HttpException(
                    SessionToken::EXCEPTION_INVALID,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Redirect to get a token
            return $this->unauthenticatedRedirect(
                $this->getShopDomainFromRequest($request)->toNative()
            );
        }

        try {
            // Try and process the token
            $token = SessionToken::fromNative($tokenSource);
        } catch (AssertionFailedException $e) {
            if ($request->ajax()) {
                // AJAX, return HTTP exception
                throw new HttpException(
                    $e->getMessage(),
                    $e->getMessage() === SessionToken::EXCEPTION_EXPIRED
                        ? Response::HTTP_FORBIDDEN
                        : Response::HTTP_BAD_REQUEST
                );
            }

            // Redirect to get a new token
            return $this->unauthenticatedRedirect(
                ! $token->getShopDomain()->isNull()
                    ? $token->getShopDomain()->toNative()
                    : $this->getShopDomainFromRequest($request)->toNative()
            );
        }
    }

    /**
     * Verify HMAC data, if present.
     *
     * @param Request $request The request object.
     *
     * @throws SignatureVerificationException
     *
     * @return void
     */
    protected function verifyHmac(Request $request): void
    {
        $hmac = $this->getHmacFromRequest($request);
        if ($hmac !== null) {
            // We have HMAC, validate it
            $data = $this->getRequestData($request, $hmac[1]);
            if (! $this->apiHelper->verifyRequest($data)) {
                // Something didn't match
                throw new SignatureVerificationException('Unable to verify signature.');
            }
        }
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
    protected function getShopDomainFromRequest(Request $request): ShopDomainValue
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

                return Arr::get($refererQueryParams, 'shop');
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
     * Grab the HMAC value, if present, and how it was found.
     * Order of precedence is:.
     *
     *  - GET/POST Variable
     *  - Headers
     *  - Referer
     *
     * @param Request $request The request object.
     *
     * @return null|array
     */
    protected function getHmacFromRequest(Request $request): ?array
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
                if (! $refererQueryParams || ! isset($refererQueryParams['hmac'])) {
                    return null;
                }

                return $refererQueryParams['hmac'];
            },
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
     * Grab the request data.
     *
     * @param Request $request The request object.
     * @param string  $source  The source of the data.
     *
     * @return array
     */
    protected function getRequestData(Request $request, string $source): array
    {
        // All possible methods
        $options = [
            // GET/POST
            DataSource::INPUT()->toNative() => function () use ($request): array {
                // Verify
                $verify = [];
                foreach ($request->query() as $key => $value) {
                    $verify[$key] = $this->parseDataSourceValue($value);
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
                        $verify[$key] = $this->parseDataSourceValue($value);
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
                    $verify[$key] = $this->parseDataSourceValue($value);
                }

                return $verify;
            },
        ];

        return $options[$source]();
    }

    /**
     * Redirect to unauthenticated route.
     *
     * @param ShopDomainValue $shopDomain The shop domain.
     *
     * @return RedirectResponse
     */
    protected function unauthenticatedRedirect(ShopDomainValue $shopDomain): RedirectResponse
    {
        return Redirect::route(
            getShopifyConfig('route_names.unauthenticated'),
            ['shop' => $shopDomain]
        );
    }

    /**
     * Parse the data source value.
     * Handle simple key/values, arrays, and nested arrays.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function parseDataSourceValue($value): string
    {
        /**
         * Format the value.
         *
         * @param mixed $val
         *
         * @return string
         */
        $formatValue = function ($val): string {
            return is_array($val) ? '["'.implode('", "', $val).'"]' : $val;
        };

        // Nested array
        if (is_array($value) && is_array(current($value))) {
            return implode(', ', array_map($formatValue, $value));
        }

        // Array or basic value
        return $formatValue($value);
    }
}
