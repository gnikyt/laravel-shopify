<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Assert\AssertionFailedException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Exceptions\HttpException;
use Osiset\ShopifyApp\Objects\Values\SessionToken;
use function Osiset\ShopifyApp\getShopifyConfig;

class VerifyShopify
{
    public function __construct()
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Get the shop domain from request
        $shopDomain = $this->getShopDomainFromRequest($request);

        // Get the token (if available)
        $tokenSource = $request->ajax() ? $request->bearerToken() : $request->query('token');
        if (empty($tokenSource)) {
            if ($request->ajax()) {
                // AJAX, return HTTP exception
                throw new HttpException(SessionToken::EXCEPTION_INVALID, Response::HTTP_BAD_REQUEST);
            } else {
                // Redirect to get a token
                return Redirect::route(
                    getShopifyConfig('route_names.unauthenticated'),
                    ['shop' => $shopDomain]
                );
            }
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
            } else {
                // Redirect to get a new token
                return Redirect::route(
                    getShopifyConfig('route_names.unauthenticated'),
                    ['shop' => $shopDomain]
                );
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
}
