<?php

namespace OhMyBrew\ShopifyApp\Test;

use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Shop;

class HelpersTest extends TestCase
{
    public function testRouteIsFormedWithShop()
    {
        // Create a shop and make it the session
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // See if it matches
        $this->assertEquals(
            'http://localhost?shop='.$shop->shopify_domain,
            \shop_route('home')
        );
    }

    public function testRouteIsFormedWithoutShop()
    {
        // See if it matches
        $this->assertEquals(
            'http://localhost',
            \shop_route('home')
        );
    }
}
