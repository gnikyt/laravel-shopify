<?php

namespace OhMyBrew\ShopifyApp\Libraries;

use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Plan;

class BillingPlan
{
    /**
     * The shop to target billing.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * The plan to use.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Plan
     */
    protected $plan;

    /**
     * The charge ID.
     *
     * @var int
     */
    protected $chargeId;

    /**
     * Constructor for billing plan class.
     *
     * @param Shop $shop The shop to target for billing.
     * @param Plan $plan The plan from the database.
     *
     * @return $this
     */
    public function __construct(Shop $shop, Plan $plan)
    {
        $this->shop = $shop;
        $this->plan = $plan;

        return $this;
    }

    /**
     * Sets the charge ID.
     *
     * @param int $chargeId The charge ID to use
     *
     * @return $this
     */
    public function setChargeId(int $chargeId)
    {
        $this->chargeId = $chargeId;

        return $this;
    }

    /**
     * Gets the charge information for a previously inited charge.
     *
     * @return object
     */
    public function getCharge()
    {
        // Check if we have a charge ID to use
        if (!$this->chargeId) {
            throw new Exception('Can not get charge information without charge ID.');
        }

        // Run API to grab details
        return $this->shop->api()->rest(
            'GET',
            "/admin/{$this->plan->typeAsString(true)}/{$this->chargeId}.json"
        )->body->{$this->plan->typeAsString()};
    }

    /**
     * Activates a plan to the shop.
     *
     * Example usage:
     * (new BillingPlan([shop], [plan]))->setChargeId(request('charge_id'))->activate();
     *
     * @return object
     */
    public function activate()
    {
        // Check if we have a charge ID to use
        if (!$this->chargeId) {
            throw new Exception('Can not activate plan without a charge ID.');
        }

        // Activate and return the API response
        return $this->shop->api()->rest(
            'POST',
            "/admin/{$this->plan->typeAsString(true)}/{$this->chargeId}/activate.json"
        )->body->{$this->plan->typeAsString()};
    }

    /**
     * Gets the confirmation URL to redirect the customer to.
     * This URL sends them to Shopify's billing page.
     *
     * Example usage:
     * (new BillingPlan([shop], [plan]))->setDetails($plan)->getConfirmationUrl();
     *
     * @return string
     */
    public function getConfirmationUrl()
    {
        // Build the charge array
        $chargeDetails = [
            'test'          => $this->plan->isTest(),
            'trial_days'    => $this->plan->hasTrial() ? $this->plan->trial_days : 0,
            'name'          => $this->plan->name,
            'price'         => $this->plan->price,
            'return_url'    => config('shopify-app.billing_redirect'),
        ];

        // Begin the charge request
        $charge = $this->shop->api()->rest(
            'POST',
            "/admin/{$this->plan->typeAsString(true)}.json",
            ["{$this->plan->typeAsString()}" => $chargeDetails]
        )->body->{$this->plan->typeAsString()};

        return $charge->confirmation_url;
    }
}
