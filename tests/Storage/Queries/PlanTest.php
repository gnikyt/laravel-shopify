<?php

namespace Osiset\ShopifyApp\Test\Storage\Queries;

use Osiset\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use Osiset\ShopifyApp\Objects\Values\PlanId;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;

class PlanTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Contracts\Queries\Plan
     */
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
        $this->assertNotNull($this->query->getById($plan->getId()));

        // Query non-existant
        $this->assertNull($this->query->getById(PlanId::fromNative(10)));
    }

    public function testPlanGetDefault(): void
    {
        // Query non-existant
        $this->assertNull($this->query->getDefault());

        // Create a plan
        factory(Plan::class)->states(['type_recurring', 'installable'])->create();

        // Query it
        $this->assertNotNull($this->query->getDefault());
    }

    public function testPlanGetAll(): void
    {
        // Create a plan
        factory(Plan::class)->states('type_onetime')->create();

        // Ensure we get a result
        $this->assertCount(1, $this->query->getAll());
    }
}
