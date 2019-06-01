<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

/**
 * Response for ensuring a proper webhook request.
 */
class AuthWebhook
{
    /**
     * Handle an incoming request to ensure webhook is valid.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = $request->header('x-shopify-hmac-sha256') ?: '';
        $shopDomain = $request->header('x-shopify-shop-domain');
        $data = $request->getContent();

        ! config('shopify-app.debug')
            ?: \Log::info(get_class() . ' - webhook ' . $request->getUri() . ' triggert for ' . $shopDomain);

        $hmacLocal = ShopifyApp::createHmac(['data' => $data, 'raw' => true, 'encode' => true]);
        if (!hash_equals($hmac, $hmacLocal) || empty($shopDomain)) {

            ! config('shopify-app.debug')
                ?: \Log::warning(get_class() . ' - invalid webhook signature ' . $request->getUri() . ' for shopify_domain ' . $shopDomain);
            // Issue with HMAC or missing shop header
            return Response::make('Invalid webhook signature.', 401);
        }

        // All good, process webhook
        return $next($request);
    }
}
