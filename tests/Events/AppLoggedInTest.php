<?php

namespace OhMyBrew\ShopifyApp\Test\Events;

use OhMyBrew\ShopifyApp\Events\AppLoggedIn;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionObject;

class AppLoggedInTest extends TestCase
{
    public function testEventAcceptsLoad()
    {
        $shop = factory(Shop::class)->create();
        $event = new AppLoggedIn($shop);

        $refEvent = new ReflectionObject($event);
        $refShop = $refEvent->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($shop->shopify_domain, $refShop->getValue($event)->shopify_domain);
    }
}
