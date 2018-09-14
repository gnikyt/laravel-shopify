<?php

namespace OhMyBrew\ShopifyApp\Observers;

use OhMyBrew\ShopifyApp\Models\Shop;

class ShopObserver
{
    /**
     * Listen to the shop creating event.
     *
     * @param Shop $shop
     *
     * @return void
     */
    public function creating(Shop $shop)
    {
        if (!isset($shop->namespace)) {
            // Automatically add the current namespace to new records
            $shop->namespace = config('shopify-app.namespace');
        }

        if (config('shopify-app.billing_freemium_enabled') === true && !isset($shop->freemium)) {
            // Add the freemium flag to the shop
            $shop->freemium = true;
        }
    }
}
