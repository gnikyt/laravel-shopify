<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

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
        $shop = ShopifyApp::shop();
        $shopParam = ShopifyApp::sanitizeShopDomain($request->get('shop'));

        // Check if shop has a session, also check the shops to ensure a match
        if (
            $shop === null ||
            ($shopParam && $shopParam !== $shop->shopify_domain) === true ||
            empty($shop->shopify_token) ||
            $shop->trashed()
        ) {
            // Either no shop session or shops do not match
            Session::forget('shopify_domain');

            // Set the return-to path so we can redirect after successful authentication
            Session::put('return_to', $request->fullUrl());

            return Redirect::route('authenticate', ['shop' => $shopParam]);
        }

        // Shop is OK, move on...
        $response = $next($request);
        if (!$response instanceof Response && !$response instanceof RedirectResponse) {
            // We need a response object to modify headers
            $response = new Response($response);
        }

        if (Config::get('shopify-app.esdk_enabled')) {
            // Headers applicable to ESDK only
            $response->headers->set('P3P', 'CP="Not used"');
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }
}
