<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Models\Shop;

class ShopModelTest extends TestCase
{
    public function testShopReturnsApi()
    {
        $shop = Shop::find(1);

        // First run should store the api object to api var
        $run1 = $shop->api();

        // Second run should retrive api var
        $run2 = $shop->api();

        $this->assertEquals($run1, $run2);
    }
}
