<?php

namespace OhMyBrew\ShopifyApp\Test\Facades;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

class ShopAppFacadeTest extends TestCase
{
    public function testBasic()
    {
        $method = new ReflectionMethod(ShopifyApp::class, 'getFacadeAccessor');
        $method->setAccessible(true);

        $this->assertEquals('shopifyapp', $method->invoke(null));
    }
}
