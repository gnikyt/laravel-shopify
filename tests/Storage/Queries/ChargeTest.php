<?php

namespace Osiset\ShopifyApp\Test\Storage\Queries;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;

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
        $this->assertNull($this->query->getById(ChargeId::fromNative(10)));
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
        $this->assertNull($this->query->getByReference(ChargeReference::fromNative(10)));
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
        $this->assertNull($this->query->getByReferenceAndShopId(ChargeReference::fromNative(10), ShopId::fromNative(10)));
    }
}
