<?php

namespace OhMyBrew\ShopifyApp\Test\Traits;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken;

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
        $this->assertInstanceOf(IApiHelper::class, $shop->apiHelper());
        $this->assertInstanceOf(BasicShopifyAPI::class, $shop->api());
    }
}
