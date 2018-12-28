<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    /**
     * The shop object.
     *
     * @var object
     */
    protected $shop;

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
        if (Config::get('shopify-app.billing_enabled') === true) {
            $this->shop = ShopifyApp::shop();
            if (!$this->shop->isFreemium() && !$this->shop->isGrandfathered() && !$this->shop->plan) {
                // They're not grandfathered in, and there is no charge
                // or charge was declined...check for tiered billing
                if (Config::get('shopify-app.billing_tiered_pricing_enabled') === true) {
                    // Get the id of the plan that is associated with the shops active billing plan
                    $plan_id = $this->getPlanId();

                    // Redirect to billing with shops plan info
                    return redirect()->route('billing', $plan_id);
                }

                // Redirect to default billing
                return Redirect::route('billing');
            }
        }

        // Move on, everything's fine
        return $next($request);
    }

    /**
     * Get the id of the plan based on the shops billing plan
     *
     * @return integer|null
     */
    protected function getPlanId()
    {
        // Get a lower case version of the plans name
        // ex: "basic", ""
        $shop_plan = $this->shop->api()->rest('GET', '/admin/shop.json')
            ->body
            ->shop
            ->plan_name;

        try {
            $plan = Plan::where('shop_plan', $shop_plan)
                ->firstOrFail();
            return $plan->id;
        } catch(ModelNotFoundException $e) {
            return null;
        }
    }
}
