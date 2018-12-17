<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Requests\StoreUsageCharge;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
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
     * @param \OhMyBrew\ShopifyApp\Models\Plan $billingPlan The plan.
     *
     * @return \Illuminate\View\View
     */
    public function index(Plan $billingPlan)
    {
        // Get the confirmation URL
        $bp = new BillingPlan(ShopifyApp::shop(), $billingPlan);
        $url = $bp->confirmationUrl();

        // Do a fullpage redirect
        return View::make('shopify-app::billing.fullpage_redirect', compact('url'));
    }

    /**
     * Processes the response from the customer.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Plan $billingPlan The plan.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Plan $billingPlan)
    {
        // Activate the plan and save
        $shop = ShopifyApp::shop();
        $bp = new BillingPlan($shop, $billingPlan);
        $bp->setChargeId(Request::query('charge_id'));
        $bp->activate();
        $bp->save();

        // All good, update the shop's plan and take them off freemium (if applicable)
        $shop->update([
            'freemium' => false,
            'plan_id'  => $billingPlan->id,
        ]);

        // Go to homepage of app
        return Redirect::route('home');
    }

    /**
     * Allows for setting a usage charge.
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
        return isset($validated['redirect']) ? Redirect::to($validated['redirect']) : Redirect::back()->with('success', true);
    }
}
