<?php namespace OhMyBrew\ShopifyApp\Test\Models;

use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

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

    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testShopShouldNotSaveWithoutDomain()
    {
        $shop = new Shop;
        $shop->shopify_token = '1234';
        $shop->save();
    }

    public function testShopShouldSaveAndAllowForMassAssignment()
    {
        $shop = new Shop;
        $shop->shopify_domain = 'hello.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->save();

        $shop = Shop::create(
            ['shopify_domain' => 'abc.myshopify.com', 'shopify_token' => '1234'],
            ['shopify_domain' => 'cba.myshopify.com', 'shopify_token' => '1234']
        );
        $this->assertEquals(true, true);
    }
}
