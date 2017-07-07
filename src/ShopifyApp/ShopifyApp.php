<?php namespace OhMyBrew\ShopifyApp;

use OhMyBrew\BasicShopifyAPI as ShopifyAPI;
use OhMyBrew\ShopifyApp\Models\Shop;
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
     * The current API instance
     *
     * @var \OhMyBrew\BasicShopifyAPI
     */
    public $api;

    /**
     * The current shop
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
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
     * Gets/sets the current shop.
     *
     * @return \OhMyBrew\Models\Shop
     */
    public function shop() {
        $shopifyDomain = session('shopify_domain');
        if (!$this->shop && $shopifyDomain) {
            // Grab shop from database here
            $shop = Shop::where('shopify_domain', $shopifyDomain)->first();

            // Update shop instance
            $this->shop = $shop;
        }

        return $this->shop;
    }

    /**
     * Gets/sets the current API instance.
     *
     * @return \OhMyBrew\BasicShopifyAPI
     */
    public function api() {
        $shopifyDomain = session('shopify_domain');
        if (!$this->api && $shopifyDomain) {
            // Grab shop from database here

            // Update API instance
            $api = new ShopifyAPI;
            $this->api = $api;
        }

        return $this->api;
    }
}
