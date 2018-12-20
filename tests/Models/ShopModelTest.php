<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopModelTest extends TestCase
{
    public function testShopReturnsApi()
    {
        // Create a shop
        $shop = factory(Shop::class)->create();

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
        $shop = factory(Shop::class)->create([
            'shopify_domain' => null,
        ]);
    }

    public function testShopShouldReturnGrandfatheredState()
    {
        $shop = factory(Shop::class)->states('grandfathered')->create();
        $shop_2 = factory(Shop::class)->create();

        $this->assertTrue($shop->isGrandfathered());
        $this->assertFalse($shop_2->isGrandfathered());
    }

    public function testShopCanSoftDeleteAndBeRestored()
    {
        $shop = factory(Shop::class)->create();
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
        $shop = factory(Shop::class)->create();
        $shop_2 = factory(Shop::class)->create();
        factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop_2->id,
        ]);

        $this->assertFalse($shop->hasCharges());
        $this->assertTrue($shop_2->hasCharges());
    }

    public function testShopReturnsPlan()
    {
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create([
            'plan_id' => $plan->id,
        ]);

        $this->assertInstanceOf(Plan::class, $shop->plan);
    }

    public function testShopReturnsNoPlan()
    {
        $shop = factory(Shop::class)->create();

        $this->assertEquals(null, $shop->plan);
    }

    public function testShopIsFreemiumAndNotFreemium()
    {
        $shop = factory(Shop::class)->states('freemium')->create();
        $shop_2 = factory(Shop::class)->create();

        $this->assertTrue($shop->isFreemium());
        $this->assertFalse($shop_2->isFreemium());
    }
}
