<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Osiset\ShopifyApp\Objects\Values\Hmac;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;
use Osiset\ShopifyApp\Util;

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
        $hmacLocal = Util::createHmac(
            [
                'data'   => $data,
                'raw'    => true,
                'encode' => true,
            ],
            Util::getShopifyConfig('api_secret', $shop)
        );

        if (! $hmac->isSame($hmacLocal) || $shop->isNull()) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid webhook signature.', HttpResponse::HTTP_UNAUTHORIZED);
        }

        // All good, process webhook
        return $next($request);
    }
}
