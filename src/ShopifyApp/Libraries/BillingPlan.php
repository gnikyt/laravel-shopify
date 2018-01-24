<?php namespace OhMyBrew\ShopifyApp\Libraries;

use \Exception;
use OhMyBrew\Models\Shop;

class BillingPlan
{
    /**
     * The shop to target billing
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * The plan details for Shopify
     *
     * @var array
     */
    protected $details;

    /**
     * The charge ID
     *
     * @var integer
     */
    protected $charge_id;

    /**
     * The charge type
     *
     * @var string
     */
    protected $charge_type;

    /**
     * Constructor for billing plan class
     *
     * @param Shop $shop The shop to target for billing.
     * @param string $charge_type The type of charge for the plan (single or recurring).
     * @return $this
     */
    public function __constructor(Shop $shop, string $charge_type)
    {
        $this->shop = $shop;
        $this->charge_type = $charge_type === 'single' ? 'application_charge' : 'recurring_application_charge';

        return $this;
    }

    /**
     * Sets the plan.
     *
     * @param array $plan The plan details.
     *      $plan = [
     *          'name'         => (string) Plan name.
     *          'price'        => (float) Plan price. Required.
     *          'test'         => (boolean) Test mode or not.
     *          'trial_days'   => (int) Plan trial period in days.
     *          'return_url'   => (string) URL to handle response for acceptance or decline or billing. Required.
     *      ]
     * @return $this
     */
    public function setDetails(array $details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Sets the charge ID.
     *
     * @param int $charge_id The charge ID to use
     * @return $this
     */
    public function setChargeId(int $charge_id)
    {
        $this->charge_id = $charge_id;

        return $this;
    }

    /**
     * Gets the charge information for a previously inited charge.
     *
     * @return object
     */
    public function getCharge()
    {
        // Run API to grab details
        return $this->shop->api()->request(
            'GET',
            "/admin/{$this->charge_type}s/{$this->charge_id}.json"
        )->body->{$this->charge_type};
    }

    /**
     * Activates a plan to the shop.
     *
     * Example usage:
     * (new BillingPlan([shop], 'recurring'))->setChargeId(request('charge_id'))->activate();
     *
     * @return object
     */
    public function activate()
    {
        // Check if we have a charge ID to use
        if (!$this->charge_id) {
            throw new Exception('Can not activate plan without a charge ID.');
        }

        // Activate and return the API response
        return $this->shop->api()->request(
            'POST',
            "/admin/{$this->charge_type}s/{$this->charge_id}/activate.json"
        );
    }

    /**
     * Gets the confirmation URL to redirect the customer to.
     * This URL sends them to Shopify's billing page.
     *
     * Example usage:
     * (new BillingPlan([shop], 'recurring'))->setDetails($plan)->getConfirmationUrl();
     *
     * @return string
     */
    public function getConfirmationUrl()
    {
        // Check if we have plan details
        if (!is_array($this->details)) {
            throw new Exception('Plan details are missing for confirmation URL request.');
        }

        // Begin the charge request
        $charge = $this->shop->api()->request(
            'POST',
            "/admin/{$this->charge_type}s.json",
            [
                "{$this->charge_type}" => [
                    'name'       => $this->plan['name'],
                    'price'      => $this->plan['price'],
                    'test'       => $this->plan['test'],
                    'trial_days' => $this->plan['trial_days'],
                    'return_url' => $this->plan['return_url'],
                ]
            ]
        )->body->{$this->charge_type};

        return $charge->confirmation_url;
    }
}
