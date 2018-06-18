<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Charge;

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
            // Grab the shop and last recurring or one-time charge
            $shop = ShopifyApp::shop();
            $lastCharge = $shop->charges()
                ->where(function ($query) {
                    $query->latestByType(Charge::CHARGE_RECURRING);
                })->orWhere(function ($query) {
                    $query->latestByType(Charge::CHARGE_ONETIME);
                })->latest()->first();

            if (
                !$shop->isGrandfathered() &&
                (is_null($lastCharge) || $lastCharge->isDeclined() || $lastCharge->isCancelled())
            ) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return redirect()->route('billing');
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
