<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Objects\Enums\DataSource;

/**
 * Responsible for validating the request.
 */
class VerifyShopify
{

    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    public function handle(Request $request, Closure $next)
    {
        // Verify the HMAC (if available)
        $hmacResult = $this->verifyHmac($request);
        if ($hmacResult === false) {
            // Invalid HMAC
            throw new SignatureVerificationException('Unable to verify signature.');
        }

        return $next($request);
    }

    /**
     * Verify HMAC data, if present.
     *
     * @param Request $request The request object.
     *
     * @throws SignatureVerificationException
     *
     * @return bool|null
     */
    protected function verifyHmac(Request $request): ?bool
    {
        $hmac = $this->getHmacFromRequest($request);
        if ($hmac['source'] === null) {
            // No HMAC, skip
            return null;
        }

        // We have HMAC, validate it
        $data = $this->getRequestData($request, $hmac['source']);

        return $this->apiHelper->verifyRequest($data);
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
     * @return array
     */
    protected function getHmacFromRequest(Request $request): array
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
                return ['source' => $method, 'value' => $value];
            }
        }

        return ['source' => null, 'value' => null];
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
                    'shop' => $shop,
                    'hmac' => $signature,
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
