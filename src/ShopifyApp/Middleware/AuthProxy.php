<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class AuthProxy
{
    /**
     * Handle an incoming request to ensure it is valid.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the query parameters we need, remove signature since its not part of the signature calculation
        $query = $request->query->all();
        $signature = $query['signature'];
        unset($query['signature']);

        // Build a local signature
        $signatureLocal = ShopifyApp::createHmac(['data' => $query, 'buildQuery' => true]);
        if ($signature !== $signatureLocal || !isset($query['shop'])) {
            // Issue with HMAC or missing shop header
            abort(401, 'Invalid proxy signature');
        }

        // Save shop domain to session
        session(['shopify_domain' => ShopifyApp::sanitizeShopDomain(request('shop'))]);

        // All good, process proxy request
        return $next($request);
    }
}
