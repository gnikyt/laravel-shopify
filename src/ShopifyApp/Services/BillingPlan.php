<?php

namespace OhMyBrew\ShopifyApp\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;

/**
 * Responsible for creating a confirmation URL for a billing plan,
 * activation of a billing plan, and getting the charge details.
 */
class BillingPlan
{
    /**
     * The shop.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * The shop API.
     *
     * @var \OhMyBrew\BasicShopifyAPI
     */
    protected $api;

    /**
     * The plan to use.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Plan
     */
    protected $plan;

    /**
     * The charge ID.
     *
     * @var int|string
     */
    protected $chargeId;

    /**
     * Response to the charge activation.
     *
     * @var object
     */
    protected $response;

    /**
     * Constructor for billing plan class.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop to target for billing.
     * @param \OhMyBrew\ShopifyApp\Models\Plan $plan The plan from the database.
     *
     * @return self
     */
    public function __construct(Shop $shop, Plan $plan)
    {
        $this->shop = $shop;
        $this->api = $this->shop->api();
        $this->plan = $plan;

        return $this;
    }

    /**
     * Sets the charge ID.
     *
     * @param int|string $chargeId The charge ID to use
     *
     * @return $this
     */
    public function setChargeId($chargeId)
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
        return $this->api->rest(
            'GET',
            "/admin/{$this->plan->typeAsString(true)}/{$this->chargeId}.json"
        )->body->{$this->plan->typeAsString()};
    }

    /**
     * Gets the confirmation URL to redirect the customer to.
     * This URL sends them to Shopify's billing page.
     *
     * Example usage:
     * (new BillingPlan([shop], [plan]))->setDetails($plan)->confirmationUrl();
     *
     * @return string
     */
    public function confirmationUrl()
    {
        // Begin the charge request
        $charge = $this->api->rest(
            'POST',
            "/admin/{$this->plan->typeAsString(true)}.json",
            ["{$this->plan->typeAsString()}" => $this->chargeParams()]
        )->body->{$this->plan->typeAsString()};

        return $charge->confirmation_url;
    }

    /**
     * Returns the charge params sent with the post request.
     *
     * @return array
     */
    public function chargeParams()
    {
        // Build the charge array
        $chargeDetails = [
            'name'          => $this->plan->name,
            'price'         => $this->plan->price,
            'test'          => $this->plan->isTest(),
            'trial_days'    => $this->plan->hasTrial() ? $this->plan->trial_days : 0,
            'return_url'    => URL::secure(Config::get('shopify-app.billing_redirect'), ['plan_id' => $this->plan->id]),
        ];

        // Handle capped amounts for UsageCharge API
        if (isset($this->plan->capped_amount)) {
            $chargeDetails['capped_amount'] = $this->plan->capped_amount;
            $chargeDetails['terms'] = $this->plan->terms;
        }

        return $chargeDetails;
    }

    /**
     * Activates a plan to the shop.
     *
     * Example usage:
     * (new BillingPlan([shop], [plan]))->setChargeId((int) request('charge_id'))->activate();
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
        $this->response = $this->api->rest(
            'POST',
            "/admin/{$this->plan->typeAsString(true)}/{$this->chargeId}/activate.json"
        )->body->{$this->plan->typeAsString()};

        return $this->response;
    }

    /**
     * Cancels the current charge for the shop, saves the new charge.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->response) {
            throw new Exception('No activation response was recieved.');
        }

        // Cancel the last charge
        $planCharge = $this->shop->planCharge();
        if ($planCharge && !$planCharge->isDeclined() && !$planCharge->isCancelled()) {
            $planCharge->cancel();
        }

        // Create a charge
        $charge = Charge::firstOrNew([
            'type'      => $this->plan->type,
            'plan_id'   => $this->plan->id,
            'shop_id'   => $this->shop->id,
            'charge_id' => $this->chargeId,
            'status'    => $this->response->status,
        ]);

        if ($this->plan->isType(Plan::PLAN_RECURRING)) {
            // Recurring plan specifics
            $charge->billing_on = $this->response->billing_on;
            $charge->trial_ends_on = $this->response->trial_ends_on;
        }

        // Set the activated on, try for the API, fallback to today
        $charge->activated_on = $this->response->activated_on ?? Carbon::today()->format('Y-m-d');

        // Merge in the plan details since the fields match the database columns
        $planDetails = $this->chargeParams();
        unset($planDetails['return_url']);
        foreach ($planDetails as $key => $value) {
            $charge->{$key} = $value;
        }

        // Finally, save the charge
        return $charge->save();
    }
}
