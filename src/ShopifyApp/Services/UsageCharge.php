<?php

namespace OhMyBrew\ShopifyApp\Services;

use Exception;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

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
     * Constructor for usage charge class
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop to target for billing.
     * @param array                            $data The usage charge data.
     *
     * @return self
     */
    public function __construct(Shop $shop, array $data)
    {
        $this->shop = $shop;
        $this->api  = $this->shop->api();
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
                    'price'       => $data['price'],
                    'description' => $data['description'],
                ],
            ]
        )->body->usage_charge;

        return $this->response;
    }
}
