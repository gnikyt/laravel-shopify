<?php namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;

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
        if (config('shopify_app.billing_enabled') === true)
        {
            $shop = ShopifyApp::shop();
            // ...
        }

        // Move on, everything's fine
        return $next($request);
    }
}
