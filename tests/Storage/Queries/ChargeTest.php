<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Queries;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;

class ChargeTest extends TestCase
{
    protected $query;
    protected $shop;

    public function setUp(): void
    {
        parent::setUp();

        $this->query = $this->app->make(IChargeQuery::class);
        $this->shop = factory($this->model)->create();
    }

    public function testChargeGetById(): void
    {
        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'user_id' => $this->shop->getId()->toNative(),
        ]);

        // Query it
        $this->assertNotNull($this->query->getById($charge->getId()));

        // Query non-existant
        $this->assertNull($this->query->getById(new ChargeId(10)));
    }

    public function testChargeGetByChargeReference(): void
    {
        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'user_id' => $this->shop->getId()->toNative(),
        ]);

        // Query it
        $this->assertNotNull($this->query->getByReference($charge->getReference()));

        // Query non-existant
        $this->assertNull($this->query->getByReference(new ChargeReference(10)));
    }

    public function testPlangetByReferenceAndShopId(): void
    {
        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'user_id' => $this->shop->getId()->toNative(),
        ]);

        // Query it
        $this->assertNotNull(
            $this->query->getByReferenceAndShopId($charge->getReference(), $this->shop->getId())
        );

        // Query non-existant
        $this->assertNull($this->query->getByReferenceAndShopId(new ChargeReference(10), new ShopId(10)));
    }
}
