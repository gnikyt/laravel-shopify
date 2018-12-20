<?php

namespace OhMyBrew\ShopifyApp\Services;

use Exception;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;

/**
 * Responsible for creating usage charges.
 */
class UsageCharge
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
     * The usage charge data.
     *
     * @var array
     */
    protected $data;

    /**
     * Response to the charge activation.
     *
     * @var object
     */
    protected $response;

    /**
     * Constructor for usage charge class.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop to target for billing.
     * @param array                            $data The usage charge data.
     *
     * @return self
     */
    public function __construct(Shop $shop, array $data)
    {
        $this->shop = $shop;
        $this->api = $this->shop->api();
        $this->data = $data;

        return $this;
    }

    /**
     * Activates the usage charge.
     *
     * @return object
     */
    public function activate()
    {
        // Ensure we have a recurring charge
        $currentCharge = $this->shop->planCharge();
        if (!$currentCharge->isType(Charge::CHARGE_RECURRING)) {
            throw new Exception('Can only create usage charges for recurring charge.');
        }

        $this->response = $this->api->rest(
            'POST',
            "/admin/recurring_application_charges/{$currentCharge->charge_id}/usage_charges.json",
            [
                'usage_charge' => [
                    'price'       => $this->data['price'],
                    'description' => $this->data['description'],
                ],
            ]
        )->body->usage_charge;

        return $this->response;
    }

    /**
     * Saves the usage charge to the database.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->response) {
            throw new Exception('No activation response was recieved.');
        }

        // Get the plan charge
        $planCharge = $this->shop->planCharge();

        // Create the charge
        $charge = new Charge();
        $charge->type = Charge::CHARGE_USAGE;
        $charge->reference_charge = $planCharge->charge_id;
        $charge->shop_id = $this->shop->id;
        $charge->charge_id = $this->response->id;
        $charge->price = $this->response->price;
        $charge->description = $this->response->description;
        $charge->billing_on = $this->response->billing_on;

        return $charge->save();
    }
}
