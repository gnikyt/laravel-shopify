<?php

namespace OhMyBrew\ShopifyApp\Observers;

class ShopObserver
{
    /**
     * Listen to the shop creating event.
     *
     * @param object $shop An instance of a shop.
     *
     * @return void
     */
    public function creating(object $shop)
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
