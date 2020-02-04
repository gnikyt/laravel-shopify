<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Queries;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;

class PlanTest extends TestCase
{
    protected $query;

    public function setUp(): void
    {
        parent::setUp();

        $this->query = $this->app->make(IPlanQuery::class);
    }

    public function testPlanGetById(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Query it
        $this->assertNotNull($this->query->getById(new PlanId($plan->id)));

        // Query non-existant
        $this->assertNull($this->query->getById(new PlanId(10)));
    }

    public function testPlanGetDefault(): void
    {
        // Query non-existant
        $this->assertNull($this->query->getDefault());

        // Create a plan
        $plan = factory(Plan::class)->states(['type_recurring', 'installable'])->create();

        // Query it
        $this->assertNotNull($this->query->getDefault());
    }

    public function testPlanGetAll(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_onetime')->create();

        // Ensure we get a result
        $this->assertEquals(1, $this->query->getAll()->count());
    }
}
