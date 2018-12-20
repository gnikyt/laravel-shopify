<?php

namespace OhMyBrew\ShopifyApp\Test\Observers;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopObserverTest extends TestCase
{
    public function testObserverAddsNamespace()
    {
        Config::set('shopify-app.namespace', 'shopify-test-namespace');

        $shop = factory(Shop::class)->create();

        $this->assertEquals('shopify-test-namespace', $shop->namespace);
    }

    public function testObserverSetsFreemiumFlag()
    {
        Config::set('shopify-app.billing_freemium_enabled', true);

        $shop = factory(Shop::class)->create();

        $this->assertTrue($shop->isFreemium());
    }
}
