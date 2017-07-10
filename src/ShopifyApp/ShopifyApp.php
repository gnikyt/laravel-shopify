<?php namespace OhMyBrew\ShopifyApp;

use Illuminate\Foundation\Application;
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
}
