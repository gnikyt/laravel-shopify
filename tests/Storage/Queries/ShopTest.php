<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Queries;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;

class ShopTest extends TestCase
{
    protected $query;
    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->query = $this->app->make(IShopQuery::class);
        $this->model = $this->app['config']->get('auth.providers.users.model');
    }

    public function testShopGetById(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Query it
        $this->assertNotNull($this->query->getById(new ShopId($shop->id)));

        // Query non-existant
        $this->assertNull($this->query->getById(new ShopId(10)));
    }

    public function testShopGetByDomain(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Query it
        $this->assertNotNull($this->query->getByDomain(new ShopDomain($shop->name)));

        // Query non-existant
        $this->assertNull($this->query->getByDomain(new ShopDomain('non-existant.myshopify.com')));
    }

    public function testShopGetAll(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Ensure we get a result
        $this->assertEquals(1, $this->query->getAll()->count());
    }
}
