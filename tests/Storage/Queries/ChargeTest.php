<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Queries;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;

class ChargeTest extends TestCase
{
    protected $query;
    protected $shop;

    public function setUp(): void
    {
        parent::setUp();

        $this->query = $this->app->make(IChargeQuery::class);
        $this->shop = factory($this->app['config']->get('auth.providers.users.model'))->create();
    }

    public function testChargeGetById(): void
    {
        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $this->shop->id,
        ]);

        // Query it
        $this->assertNotNull($this->query->getById(new ChargeId($charge->id)));

        // Query non-existant
        $this->assertNull($this->query->getById(new ChargeId(10)));
    }

    public function testPlanGetByShopIdAndChargeId(): void
    {
        // Create a charge
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $this->shop->id,
        ]);

        // Query it
        $this->assertNotNull(
            $this->query->getByShopIdAndChargeId(new ShopId($this->shop->id), new ChargeId($charge->id))
        );

        // Query non-existant
        $this->assertNull($this->query->getByShopIdAndChargeId(new ShopId(10), new ChargeId(10)));
    }
}
