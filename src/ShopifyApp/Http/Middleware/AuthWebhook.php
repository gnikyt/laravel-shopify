<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Osiset\ShopifyApp\Objects\Values\Hmac;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;

use function Osiset\ShopifyApp\createHmac;
use function Osiset\ShopifyApp\getShopifyConfig;

/**
 * Response for ensuring a proper webhook request.
 */
class AuthWebhook
{
    /**
     * Handle an incoming request to ensure webhook is valid.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = Hmac::fromNative($request->header('x-shopify-hmac-sha256', ''));
        $shop = NullableShopDomain::fromNative($request->header('x-shopify-shop-domain'));
        $data = $request->getContent();
        $hmacLocal = createHmac(
            [
                'data'   => $data,
                'raw'    => true,
                'encode' => true,
            ],
            getShopifyConfig('api_secret', $shop)
        );

        if (! $hmac->isSame($hmacLocal) || $shop->isNull()) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid webhook signature.', 401);
        }

        // All good, process webhook
        return $next($request);
    }
}
