<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Response for ensuring an authenticated shop.
 */
class AuthShop
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $shopParam = ShopifyApp::sanitizeShopDomain($request->get('shop'));
        $shop = ShopifyApp::shop($shopParam);

        // Check if shop has a session, also check the shops to ensure a match
        if (
            $shop === null ||
            $shop->trashed() ||
            empty($shop->shopify_token) ||
            ($shopParam && $shopParam !== $shop->shopify_domain) === true
        ) {
            // Either no shop session or shops do not match
            Session::forget('shopify_domain');

            // Set the return-to path so we can redirect after successful authentication
            Session::put('return_to', $request->fullUrl());

            return Redirect::route('authenticate', ['shop' => $shopParam]);
        }

        // Shop is OK, now check if ESDK is enabled and this is not a JSON/AJAX request...
        $response = $next($request);
        if (
            Config::get('shopify-app.esdk_enabled') &&
            ($request->ajax() || $request->expectsJson() || $request->isJson()) === false
        ) {
            if (($response instanceof BaseResponse) === false) {
                // Not an instance of a Symfony response, override
                $response = new Response($response);
            }

            // Attempt to modify headers applicable to ESDK (does not work in all cases)
            $response->headers->set('P3P', 'CP="Not used"');
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }
}
