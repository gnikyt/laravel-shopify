<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Libraries\BillingPlan;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;

trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param int|null $planId The plan's ID.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($planId = null)
    {
        // Get the confirmation URL
        $shop = ShopifyApp::shop();
        $billingPlan = new BillingPlan($shop, $this->getPlan($planId));

        // Do a fullpage redirect
        return view('shopify-app::billing.fullpage_redirect', [
            'url' => $billingPlan->getConfirmationUrl(),
        ]);
    }

    /**
     * Processes the response from the customer.
     *
     * @param int|null $planId The plan's ID.
     *
     * @return \Illuminate\Http\Response
     */
    public function process($planId = null)
    {
        // Setup the shop and get the charge ID passed in
        $shop = ShopifyApp::shop();
        $chargeId = request('charge_id');

        // Setup the plan and get the charge
        $plan = $this->getPlan($planId);
        $billingPlan = new BillingPlan($shop, $plan);
        $billingPlan->setChargeId($chargeId);
        $status = $billingPlan->getCharge()->status;

        // Grab the plan detailed used
        $planDetails = $billingPlan->getChargeParams();
        unset($planDetails['return_url']);

        // Create a charge (regardless of the status)
        $charge = Charge::firstOrNew([
            'type'      => $plan->type,
            'shop_id'   => $shop->id,
            'plan_id'   => $plan->id,
            'charge_id' => $chargeId,
        ]);

        // Check the customer's answer to the billing
        if ($status === 'accepted') {
            // Activate and add details to our charge
            $response = $billingPlan->activate();
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
        $charge->save();

        if ($status === 'declined') {
            // Show the error... don't allow access
            return view('shopify-app::billing.error', [
                'message' => 'It seems you have declined the billing charge for this application'
            ]);
        }

        // All good, update the shop's plan and take them off freeium (if applicable)
        $shop->freemium = false;
        $shop->plan_id = $plan->id;
        $shop->save();

        // Go to homepage of app
        return redirect()->route('home');
    }

    /**
     * Allows for setting a usage charge.
     *
     * @return \Illuminate\Http\Response
     */
    public function usageCharge()
    {
        $shop = ShopifyApp::shop();
        $lastCharge = $this->getLastCharge($shop);

        if ($lastCharge->type !== Charge::CHARGE_RECURRING) {
            // Charge is not recurring
            return view('shopify-app::billing.error', ['message' => 'Can only create usage charges for recurring charge']);
        }

        // Get the input values needed
        $data = request()->only(['price', 'description', 'redirect', 'signature']);
        $signature = $data['signature'];
        unset($data['signature']);

        // Confirm the charge hasn't been tampered with
        $signatureLocal = ShopifyApp::createHmac(['data' => $data, 'buildQuery' => true]);
        if (!hash_equals($signature, $signatureLocal)) {
            // Possible tampering
            return view('shopify-app::billing.error', ['message' => 'Issue in creating usgae charge']);
        }

        // Create the charge via API
        $usageCharge = $shop->api()->rest(
            'POST',
            "/admin/recurring_application_charges/{$lastCharge->charge_id}/usage_charges.json",
            [
                'price'       => $data['price'],
                'description' => $data['description'],
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
        return isset($data['redirect']) ? redirect($data['redirect']) : redirect()->back()->with('success', true);
    }

    /**
     * Get the plan to use.
     *
     * @param int|null $planId The plan's ID.
     *
     * @return Plan
     */
    protected function getPlan($planId = null)
    {
        if ($planId === null) {
            // Find the on-install plan
            return Plan::where('on_install', true)->first();
        }

        // Find the plan passed to the method
        return Plan::where('id', $planId)->first();
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
            ->where('plan_id', $shop->plan_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
