<?php

namespace OhMyBrew\ShopifyApp\Traits;

use StoreUsageCharge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\BillingPlan;
use OhMyBrew\ShopifyApp\Services\UsageCharge;

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
        // Setup the plan and activate
        $shop = ShopifyApp::shop();
        $bp = new BillingPlan($shop, $billingPlan);
        $bp->setChargeId(Request::query('charge_id'));
        $bp->activate();
        $bp->save();

        // All good, update the shop's plan and take them off freemium (if applicable)
        $shop->update([
            'freemium' => false,
            'plan_id'  => $plan->id,
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
        $uc = new UsageCharge(ShopifyApp::shop(), $request->validated());
        $uc->activate();

/*        $shop = ShopifyApp::shop();
        $lastCharge = $this->getLastCharge($shop);

        if ($lastCharge->type !== Charge::CHARGE_RECURRING) {
            // Charge is not recurring
            return view('shopify-app::billing.error', ['message' => 'Can only create usage charges for recurring charge']);
        }

        // Create the charge via API
        $usageCharge = $shop->api()->rest(
            'POST',
            "/admin/recurring_application_charges/{$lastCharge->charge_id}/usage_charges.json",
            [
                'usage_charge' => [
                    'price'       => $data['price'],
                    'description' => $data['description'],
                ],
            ]
        )->body->usage_charge;

        // Create the charge in the database referencing the recurring charge
        $charge = new Charge();
        $charge->type = Charge::CHARGE_USAGE;
        $charge->shop_id = $shop->id;
        $charge->reference_charge = $lastCharge->charge_id;
        $charge->charge_id = $usageCharge->id;
        $charge->price = $usageCharge->price;
        $charge->description = $usageCharge->description;
        $charge->billing_on = $usageCharge->billing_on;
        $charge->save();

        // All done, return with success
        return isset($data['redirect']) ? redirect($data['redirect']) : redirect()->back()->with('success', true);*/
    }
}
