<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Requests\StoreUsageCharge;
use OhMyBrew\ShopifyApp\Services\BillingPlan;
use OhMyBrew\ShopifyApp\Services\UsageCharge;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Plan $plan The plan.
     *
     * @return \Illuminate\View\View
     */
    public function index(Plan $plan)
    {
        // If the plan is null, get a plan
        if (is_null($plan) || ($plan && !$plan->exists)) {
            $plan = Plan::where('on_install', true)->first();
        }

        // Get the confirmation URL
        $bp = new BillingPlan(ShopifyApp::shop(), $plan);
        $url = $bp->confirmationUrl();

        // Do a fullpage redirect
        return View::make('shopify-app::billing.fullpage_redirect', compact('url'));
    }

    /**
     * Processes the response from the customer.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Plan $plan The plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Plan $plan)
    {
        // Activate the plan and save
        $shop = ShopifyApp::shop();
        $bp = new BillingPlan($shop, $plan);
        $bp->setChargeId(Request::query('charge_id'));
        $bp->activate();
        $bp->save();

        // All good, update the shop's plan and take them off freemium (if applicable)
        $shop->update([
            'freemium' => false,
            'plan_id'  => $plan->id,
        ]);

        // Go to homepage of app
        return Redirect::route('home')->with('success', 'billing');
    }

    /**
     * Allows for setting a usage charge.
     *
     * @param \OhMyBrew\ShopifyApp\Requests\StoreUsageCharge $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function usageCharge(StoreUsageCharge $request)
    {
        // Activate and save the usage charge
        $validated = $request->validated();
        $uc = new UsageCharge(ShopifyApp::shop(), $validated);
        $uc->activate();
        $uc->save();

        // All done, return with success
        return isset($validated['redirect']) ?
            Redirect::to($validated['redirect'])->with('success', 'usage_charge') :
            Redirect::back()->with('success', 'usage_charge');
    }
}
