<?php

namespace OhMyBrew\ShopifyApp\Observers;

use Illuminate\Support\Facades\Config;

/**
 * Responsible for observing changes to the shop model.
 */
class ShopObserver
{
    /**
     * Listen to the shop creating event.
     *
     * @param object $shop An instance of a shop.
     *
     * @return void
     */
    public function creating($shop)
    {
        if (!isset($shop->namespace)) {
            // Automatically add the current namespace to new records
            $shop->namespace = Config::get('shopify-app.namespace');
        }

        if (Config::get('shopify-app.billing_freemium_enabled') === true && !isset($shop->freemium)) {
            // Add the freemium flag to the shop
            $shop->freemium = true;
        }
    }
}
