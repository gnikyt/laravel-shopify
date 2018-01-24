<?php namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Determine the charge type
        $charge_type = config('shopify-app.billing_type') === 'single' ? 'application_charge' : 'recurring_application_charge';

        // Get the charge object
        $charge = ShopifyApp::shop()->api()->request(
            'POST',
            "/admin/{$charge_type}s.json",
            [
                "{$charge_type}" => [
                    'name'       => config('shopify-app.billing_plan'),
                    'price'      => config('shopify-app.billing_price'),
                    'test'       => config('shopify-app.billing_test'),
                    'trial_days' => config('shopify-app.billing_trial_days'),
                    'return_url' => url(config('shopify-app.billing_redirect'))
                ]
            ]
        )->body->{$charge_type};

        // Do a fullpage redirect
        return view('shopify-app::billing.fullpage_redirect', [
            'url' => $charge->confirmation_url
        ]);
    }

    /**
     * Processes the response from the customer.
     *
     * @return void
     */
    public function process()
    {
        // Setup the shop and API
        $shop = ShopifyApp::shop();
        $api = $shop->api();

        // Get the charge ID passed back and determine the charge type
        $charge_id = request('charge_id');
        $charge_type = config('shopify-app.billing_type') === 'single' ? 'application_charge' : 'recurring_application_charge';

        // Get the charge
        $charge = $api->request(
            'GET',
            "/admin/{$charge_type}s/{$charge_id}.json"
        )->body->{$charge_type};

        if ($charge->status == 'accepted') {
            // Customer accepted, activate the charge
            $api->request('POST', "/admin/{$charge_type}s/{$charge_id}/activate.json");

            // Save the charge ID to the shop
            $shop->charge_id = $charge_id;
            $shop->save();

            // Go to homepage of app
            return redirect()->route('home');
        } else {
            // Customer declined the charge, abort
            return abort(404, 'It seems you have declined the billing charge for this application.');
        }
    }
}
