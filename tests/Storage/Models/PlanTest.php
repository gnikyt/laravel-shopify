<?php

namespace OhMyBrew\ShopifyApp\Test\Storage\Models;

use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;

class PlanTest extends TestCase
{
    public function testModel(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        $this->assertInstanceOf(PlanId::class, $plan->getId());
        $this->assertEquals(0, $plan->charges->count());
        $this->assertEquals(PlanType::RECURRING(), $plan->getType());
        $this->assertTrue($plan->isType(PlanType::RECURRING()));
        $this->assertEquals('recurring_application_charge', $plan->getTypeApiString());
        $this->assertEquals('recurring_application_charges', $plan->getTypeApiString(true));
        $this->assertFalse($plan->hasTrial());
        $this->assertFalse($plan->isOnInstall());
        $this->assertFalse($plan->isTest());
    }
}
