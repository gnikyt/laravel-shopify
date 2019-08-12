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
 *
 * @example `<a href="{{ shop_route('your-orders') }}">Orders</a>`,
 * Creates: `<a href="/your-orders?shop=example.myshopify.com">Orders</a>`
 */
function shop_route($name, array $parameters = [], bool $absolute = true)
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

/**
 * Generate the URL with the shop appended.
 *
 * @param string $url The URL to use.
 *
 * @return string
 *
 * @example `<a href="{{ shop_url('/your-orders?abc=123') }}">Orders</a>`,
 * Creates: `<a href="/your-orders?abc=123&shop=example.myshopify.com">Orders</a>`
 */
function shop_url(string $url)
{
    // Grab the current shop
    $shop = ShopifyApp::shop();

    if ($shop) {
        $part = strstr($url, '?') ? '&' : '?';
        $url .= "{$part}shop={$shop->shopify_domain}";

        return $url;
    }

    // No shop, return plain URL
    return $url;
}
