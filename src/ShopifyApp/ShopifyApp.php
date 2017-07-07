<?php namespace OhMyBrew\ShopifyApp;

use OhMyBrew\BasicShopifyAPI as ShopifyAPI;
use Illuminate\Foundation\Application;

class ShopifyApp
{
    /**
     * Laravel application
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

    /**
     * The current shop
     *
     * @var \OhMyBrew\BasicShopifyAPI
     */
    public $shop;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Gets the current shop.
     *
     * @return \OhMyBrew\BasicShopifyAPI
     */
    public function shop() {
        if ($this->shop) {
            // Return the instance
            return $this->shop;
        }

        // New instance
        $shopifyDomain = session('shopify_domain');
        if ($shopifyDomain) {
            // Grab shop from database here

            // Start the API
            $api = new ShopifyAPI;
            //$api->setSession($shopifyDomain);

            // Update shop instance
            $this->shop = $api;

            return $api;
        }

        // No shop
        return false;
    }
}
