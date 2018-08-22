<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Libraries\BillingPlan;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;

trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get the confirmation URL
        $shop = ShopifyApp::shop();
        $plan = new BillingPlan($shop, $this->chargeType());
        $plan->setDetails($this->planDetails($shop));

        // Do a fullpage redirect
        return view('shopify-app::billing.fullpage_redirect', [
            'url' => $plan->getConfirmationUrl(),
        ]);
    }

    /**
     * Processes the response from the customer.
     *
     * @return void
     */
    public function process()
    {
        // Setup the shop and get the charge ID passed in
        $shop = ShopifyApp::shop();
        $chargeId = request('charge_id');

        // Setup the plan and get the charge
        $plan = new BillingPlan($shop, $this->chargeType());
        $plan->setChargeId($chargeId);
        $status = $plan->getCharge()->status;

        // Grab the plan detailed used
        $planDetails = $this->planDetails($shop);
        unset($planDetails['return_url']);

        // Create a charge (regardless of the status)
        $charge = new Charge();
        $charge->type = $this->chargeType() === 'recurring' ? Charge::CHARGE_RECURRING : Charge::CHARGE_ONETIME;
        $charge->charge_id = $chargeId;
        $charge->status = $status;

        // Check the customer's answer to the billing
        if ($status === 'accepted') {
            // Activate and add details to our charge
            $response = $plan->activate();
            $charge->status = $response->status;
            $charge->billing_on = $response->billing_on;
            $charge->trial_ends_on = $response->trial_ends_on;
            $charge->activated_on = $response->activated_on;

            // Set old charge as cancelled, if one
            $lastCharge = $this->getLastCharge($shop);
            if ($lastCharge) {
                $lastCharge->status = 'cancelled';
                $lastCharge->save();
            }
        } else {
            // Customer declined the charge
            $charge->status = 'declined';
            $charge->cancelled_on = Carbon::today()->format('Y-m-d');
        }

        // Merge in the plan details since the fields match the database columns
        foreach ($planDetails as $key => $value) {
            $charge->{$key} = $value;
        }

        // Save and link to the shop
        $shop->charges()->save($charge);

        if ($status === 'declined') {
            // Show the error... don't allow access
            return abort(403, 'It seems you have declined the billing charge for this application');
        }

        // All good... go to homepage of app
        return redirect()->route('home');
    }

    /**
     * Base plan to use for billing. Setup as a function so its patchable.
     * Checks for cancelled charge within trial day limit, and issues
     * a new trial days number depending on the result for shops who
     * resinstall the app.
     *
     * @param object $shop The shop object.
     *
     * @return array
     */
    protected function planDetails(Shop $shop)
    {
        // Initial plan details
        $plan = [
            'name'       => config('shopify-app.billing_plan'),
            'price'      => config('shopify-app.billing_price'),
            'test'       => config('shopify-app.billing_test'),
            'return_url' => url(config('shopify-app.billing_redirect')),
        ];

        // Handle capped amounts for UsageCharge API
        if (config('shopify-app.billing_capped_amount')) {
            $plan['capped_amount'] = config('shopify-app.billing_capped_amount');
            $plan['terms'] = config('shopify-app.billing_terms');
        }

        // Grab the last charge for the shop (if any) to determine if this shop
        // reinstalled the app so we can issue new trial days based on result
        $lastCharge = $this->getLastCharge($shop);
        if ($lastCharge && $lastCharge->isCancelled()) {
            // Return the new trial days, could result in 0
            $plan['trial_days'] = $lastCharge->remainingTrialDaysFromCancel();
        } else {
            // Set initial trial days fromc config
            $plan['trial_days'] = config('shopify-app.billing_trial_days');
        }

        return $plan;
    }

    /**
     * Base charge type (single or recurring).
     * Setup as a function so its patchable.
     *
     * @return string
     */
    protected function chargeType()
    {
        return config('shopify-app.billing_type');
    }

    /**
     * Gets the last single or recurring charge for the shop.
     *
     * @param object $shop The shop object.
     *
     * @return null|Charge
     */
    protected function getLastCharge(Shop $shop)
    {
        return $shop->charges()
            ->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
