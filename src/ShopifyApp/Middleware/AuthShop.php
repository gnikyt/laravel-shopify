<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

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
        $shopParam = ShopifyApp::sanitizeShopDomain(request('shop'));

        // Check if shop has a session, also check the shops to ensure a match
        if ($shop === null || ($shopParam && $shopParam !== $shop->shopify_domain) === true) {
            // Either no shop session or shops do not match
            session()->forget('shopify_domain');

            return redirect()->route('authenticate')->with('shop', $shopParam);
        }

        // Move on, authenticated
        return $next($request);
    }
}
