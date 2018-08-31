<?php

namespace OhMyBrew\ShopifyApp\Observers;

use OhMyBrew\ShopifyApp\Models\Shop;

class ShopObserver
{
    /**
     * Listen to the shop creating event.
     *
     * @param Shop $shop
     * @return void
     */
    public function creating(Shop $shop)
    {
        // Automatically add the current namespace to new records
        $shop->namespace = config('shopify-app.namespace');
    }
}
