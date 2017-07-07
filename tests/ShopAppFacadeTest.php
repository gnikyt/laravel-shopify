<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionMethod;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class ShopifyAppFacadeTest extends TestCase
{
    public function testBasic()
    {
        $method = new ReflectionMethod(ShopifyApp::class, 'getFacadeAccessor');
        $method->setAccessible(true);

        $this->assertEquals('shopifyapp', $method->invoke(null));
    }
}