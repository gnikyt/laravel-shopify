<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\BasicShopifyAPI;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken;

class ShopModelTest extends TestCase
{
    public function testModel(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create a shop
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        $this->assertInstanceOf(ShopId::class, $shop->getId());
        $this->assertInstanceOf(ShopDomain::class, $shop->getDomain());
        $this->assertInstanceOf(AccessToken::class, $shop->getToken());
        $this->assertFalse($shop->isGrandfathered());
        $this->assertFalse($shop->isFreemium());
        $this->assertEquals(0, $shop->charges->count());
        $this->assertFalse($shop->hasCharges());
        $this->assertInstanceOf(Plan::class, $shop->plan);
        $this->assertTrue($shop->hasOfflineAccess());
        $this->assertInstanceOf(BasicShopifyAPI::class, $shop->api());
        $this->assertInstanceOf(IApiHelper::class, $shop->apiHelper());
    }
}
