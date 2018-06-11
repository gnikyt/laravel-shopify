<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use Symfony\Component\HttpFoundation\Response;

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
        if (
            $shop === null ||
            ($shopParam && $shopParam !== $shop->shopify_domain) === true ||
            $shop->shopify_token === null ||
            $shop->trashed()
        ) {
            // Either no shop session or shops do not match
            session()->forget('shopify_domain');

            return redirect()->route('authenticate', ['shop' => $shopParam]);
        }

        // Shop is OK, move on...
        $response = $next($request);
        if (!$response instanceof Response) {
            // We need a response object to modify headers
            $response = new Response($response);
        }

        if (config('shopify-app.esdk_enabled')) {
            // Headers applicable to ESDK only
            $response->headers->set('P3P', 'CP="Not used"');
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }
}
