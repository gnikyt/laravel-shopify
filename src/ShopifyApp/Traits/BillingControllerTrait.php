<?php namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Libraries\BillingPlan;

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
        $plan = new BillingPlan(ShopifyApp::shop(), $this->chargeType());
        $plan->setDetails($this->planDetails());

        // Do a fullpage redirect
        return view('shopify-app::billing.fullpage_redirect', [
            'url' => $plan->getConfirmationUrl()
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
        $charge_id = request('charge_id');

        // Setup the plan and get the charge
        $plan = new BillingPlan($shop, $this->chargeType());
        $plan->setChargeId($charge_id);

        // Check the customer's answer to the billing
        $charge = $plan->getCharge();
        if ($charge->status == 'accepted') {
            // Customer accepted, activate the charge
            $plan->activate();

            // Save the charge ID to the shop
            $shop->charge_id = $charge_id;
            $shop->save();

            // Go to homepage of app
            return redirect()->route('home');
        } else {
            // Customer declined the charge, abort
            return abort(403, 'It seems you have declined the billing charge for this application');
        }
    }

    /**
     * Base plan to use for billing.
     * Setup as a function so its patchable.
     *
     * @return array
     */
    protected function planDetails()
    {
        return [
            'name'       => config('shopify-app.billing_plan'),
            'price'      => config('shopify-app.billing_price'),
            'test'       => config('shopify-app.billing_test'),
            'trial_days' => config('shopify-app.billing_trial_days'),
            'return_url' => url(config('shopify-app.billing_redirect'))
        ];
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
}
