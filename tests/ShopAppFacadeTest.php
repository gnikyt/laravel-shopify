<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionMethod;
use OhMyBrew\ShopifyApp\Facades\ShopifyAppFacade;

class ShopifyAppFacadeTest extends TestCase
{
    public function testBasic()
    {
        $method = new ReflectionMethod(ShopifyAppFacade::class, 'getFacadeAccessor');
        $method->setAccessible(true);

        $this->assertEquals('ShopifyApp', $method->invoke(null));
    }
}