<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Actions\GetPlanUrl;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Test\Stubs\Api as ApiStub;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;

class GetPlanUrlTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(GetPlanUrl::class);
    }

    public function testRun(): void
    {
        // Create a plan
        factory(Plan::class)->states(['installable', 'type_recurring'])->create();

        // Create the shop with no plan
        $shop = factory($this->model)->create();

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges']);

        $result = call_user_func(
            $this->action,
            $shop->getId(),
            NullablePlanId::fromNative(null)
        );

        $this->assertNotEmpty($result);
    }
}
