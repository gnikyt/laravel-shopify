<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use OhMyBrew\ShopifyApp\Models\Plan;
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
        $shop = new Shop();
        $shop->shopify_token = '1234';
        $shop->save();
    }

    public function testShopShouldSaveAndAllowForMassAssignment()
    {
        $shop = new Shop();
        $shop->shopify_domain = 'hello.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->save();

        $shop = Shop::create(
            ['shopify_domain' => 'abc.myshopify.com', 'shopify_token' => '1234'],
            ['shopify_domain' => 'cba.myshopify.com', 'shopify_token' => '1234', 'grandfathered' => true]
        );
        $this->assertEquals(true, true);
    }

    public function testShopShouldReturnGrandfatheredState()
    {
        $shop = Shop::where('shopify_domain', 'grandfathered.myshopify.com')->first();
        $shop_2 = Shop::where('shopify_domain', 'example.myshopify.com')->first();

        $this->assertEquals(true, $shop->isGrandfathered());
        $this->assertEquals(false, $shop_2->isGrandfathered());
    }

    public function testShopCanSoftDeleteAndBeRestored()
    {
        $shop = new Shop();
        $shop->shopify_domain = 'hello.myshopify.com';
        $shop->save();
        $shop->delete();

        // Test soft delete
        $this->assertTrue($shop->trashed());
        $this->assertSoftDeleted('shops', [
            'id'             => $shop->id,
            'shopify_domain' => $shop->shopify_domain,
        ]);

        // Test restore
        $shop->restore();
        $this->assertFalse($shop->trashed());
    }

    public function testShouldReturnBoolForChargesApplied()
    {
        $shop = Shop::where('shopify_domain', 'grandfathered.myshopify.com')->first();
        $shop_2 = Shop::where('shopify_domain', 'example.myshopify.com')->first();

        $this->assertEquals(false, $shop->hasCharges());
        $this->assertEquals(true, $shop_2->hasCharges());
    }

    public function testShopReturnsPlan()
    {
        $this->assertInstanceOf(
            Plan::class,
            Shop::find(1)->plan
        );
    }

    public function testShopReturnsNoPlan()
    {
        $this->assertEquals(
            null,
            Shop::find(5)->plan
        );
    }

    public function testShopIsFreemiumAndNotFreemium()
    {
        $this->assertEquals(true, Shop::find(5)->isFreemium());
        $this->assertEquals(false, Shop::find(1)->isFreemium());
    }
}
