<?php

namespace OhMyBrew\ShopifyApp\Services;

use Exception;
use OhMyBrew\ShopifyApp\Models\Plan;
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
     * Response to the charge activation.
     *
     * @var object
     */
    protected $response;

    /**
     * Constructor for usage charge class
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop to target for billing.
     *
     * @return self
     */
    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
        $this->api  = $this->shop->api();

        return $this;
    }
}
