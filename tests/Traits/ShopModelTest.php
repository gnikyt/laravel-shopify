<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

class ShopModelTest extends TestCase
{
    public function testModel(): void
    {
        // Create a plan
        $plan = factory(Util::getShopifyConfig('models.plan', Plan::class))->states('type_recurring')->create();

        // Create a shop
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        $this->assertInstanceOf(ShopId::class, $shop->getId());
        $this->assertInstanceOf(ShopDomain::class, $shop->getDomain());
        $this->assertInstanceOf(AccessToken::class, $shop->getAccessToken());
        $this->assertFalse($shop->isGrandfathered());
        $this->assertFalse($shop->isFreemium());
        $this->assertCount(0, $shop->charges);
        $this->assertFalse($shop->hasCharges());
        $this->assertInstanceOf(Util::getShopifyConfig('models.plan', Plan::class), $shop->plan);
        $this->assertTrue($shop->hasOfflineAccess());
        $this->assertInstanceOf(BasicShopifyAPI::class, $shop->api());
        $this->assertInstanceOf(IApiHelper::class, $shop->apiHelper());
    }

    public function testOfflineToken(): void
    {
        // No token
        $shop = factory($this->model)->create([
            'password' => '',
        ]);
        $this->assertFalse($shop->hasOfflineAccess());

        // With token
        $shop->password = 'abc123';
        $shop->save();
        $shop->refresh();
        $this->assertTrue($shop->hasOfflineAccess());
    }

    public function testNamespacingAndFreemium(): void
    {
        $this->app['config']->set('shopify-app.billing_freemium_enabled', true);
        $this->app['config']->set('shopify-app.namespace', 'app');

        $shop = factory($this->model)->create();

        $this->assertSame('app', $shop->shopify_namespace);
        $this->assertTrue($shop->isFreemium());
    }
}
