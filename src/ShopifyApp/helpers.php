<?php

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

/**
 * Generate the URL to a named route with shop appended.
 *
 * @param array|string $name       The route name.
 * @param mixed        $parameters The parameters to send.
 * @param bool         $absolute   If the URL is to be absolute.
 *
 * @return string
 */
function shop_route($name, $parameters = [], $absolute = true)
{
    // Grab the current shop
    $shop = ShopifyApp::shop();

    if ($shop) {
        // We have a shop, add in the shop to the URL
        $parameters = array_merge(
            $parameters,
            ['shop' => $shop->shopify_domain]
        );
    }

    return app('url')->route($name, $parameters, $absolute);
}
