<?php

namespace OhMyBrew\ShopifyApp\Test\Observers;

use Illuminate\Support\Facades\Event;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopObserverTest extends TestCase
{
    public function testObserverAddsNamespace()
    {
        // Need a better way to test... event faking not working...
        config(['shopify-app.namespace' => 'shopify-test-namespace']);

        $shop = new Shop();
        $shop->shopify_domain = 'observer.myshopify.com';
        $shop->save();

        $this->assertEquals('shopify-test-namespace', $shop->namespace);
    }
}
