<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Commands;

use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = $this->app->make(IShopCommand::class);
    }

    public function testMake(): void
    {
        // Make the shop
        $domain = new ShopDomain('example.myshopify.com');
        $token = new AccessToken('123456');
        $shopId = $this->command->make($domain, $token);

        $this->assertInstanceOf(ShopId::class, $shopId);
    }

    public function testSetToPlan(): void
    {
        // Create a shop
        $shop = factory($this->model)->create([
            'shopify_freemium' => true,
        ]);

        $this->assertTrue(
            $this->command->setToPlan($shop->getId(), new PlanId(1))
        );
    }

    public function testSetAccessToken(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        $this->assertTrue(
            $this->command->setAccessToken($shop->getId(), new AccessToken('123456'))
        );
    }

    public function testClean(): void
    {
        // Create a shop
        $shop = factory($this->model)->create([
            'plan_id' => (new PlanId(1))->toNative(),
        ]);

        $this->assertTrue(
            $this->command->clean($shop->getId())
        );
    }

    public function testSoftDeleteAndRestore(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Soft delete
        $this->assertFalse($shop->trashed());
        $this->assertTrue(
            $this->command->softDelete($shop->getId())
        );

        $shop->refresh();

        // Confirm soft delete
        $this->assertTrue($shop->trashed());

        // Restore
        $this->assertTrue(
            $this->command->restore($shop->getId())
        );

        $shop->refresh();

        // Confirm restore
        $this->assertFalse($shop->trashed());
    }

    public function testSetAsFreemium(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        $this->assertFalse($shop->isFreemium());
        $this->assertTrue(
            $this->command->setAsFreemium($shop->getId())
        );

        $shop->refresh();

        $this->assertTrue($shop->isFreemium());
    }

    public function testSetNamespace(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();
        $namespace = 'example';

        $this->assertNull($shop->shopify_namespace);
        $this->assertTrue(
            $this->command->setNamespace($shop->getId(), $namespace)
        );

        $shop->refresh();

        $this->assertEquals($namespace, $shop->shopify_namespace);
    }
}