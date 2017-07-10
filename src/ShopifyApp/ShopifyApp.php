<?php namespace OhMyBrew\ShopifyApp;

use Illuminate\Foundation\Application;
use OhMyBrew\BasicShopifyAPI as ShopifyAPI;
use OhMyBrew\ShopifyApp\Models\Shop;

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
        if (!$this->api && $this->shop()) {
            // Update API instance
            $api = $this->createApiForShop($this->shop());
            $this->api = $api;
        }

        return $this->api;
    }

    /**
     * Creates an API instance for a shop
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop to use
     *
     * @return \OhMyBrew\BasicShopifyAPI
     */
    public function createApiForShop(Shop $shop)
    {
        return new ShopifyAPI;
    }
}
