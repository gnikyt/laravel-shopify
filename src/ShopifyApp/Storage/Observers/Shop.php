<?php

namespace OhMyBrew\ShopifyApp\Observers;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Contracts\ShopModel;

/**
 * Responsible for observing changes to the shop (user) model.
 */
class Shop
{
    /**
     * Listen to the shop creating event.
     * TODO: Move partial to command.
     *
     * @param ShopModel $shop An instance of a shop.
     *
     * @return void
     */
    public function creating(ShopModel $shop): void
    {
        if (!isset($shop->shopify_namespace)) {
            // Automatically add the current namespace to new records
            $shop->shopify_namespace = Config::get('shopify-app.namespace');
        }

        if (Config::get('shopify-app.billing_freemium_enabled') === true && !isset($shop->shopify_freemium)) {
            // Add the freemium flag to the shop
            $shop->shopify_freemium = true;
        }
    }
}
