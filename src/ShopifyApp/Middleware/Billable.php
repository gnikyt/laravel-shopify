<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class Billable
{
    /**
     * Checks if a shop has paid for access.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('shopify-app.billing_enabled') === true) {
            $shop = ShopifyApp::shop();
            if (!$shop->isPaid() && !$shop->isGrandfathered()) {
                // No charge in database and they're not grandfathered in, redirect to billing
                return redirect()->route('billing');
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
