<?php

namespace Osiset\ShopifyApp\Test\Storage\Models;

use Osiset\ShopifyApp\Objects\Enums\PlanType;
use Osiset\ShopifyApp\Objects\Values\PlanId;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;

class PlanTest extends TestCase
{
    public function testModel(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        $this->assertInstanceOf(PlanId::class, $plan->getId());
        $this->assertCount(0, $plan->charges);
        $this->assertEquals(PlanType::RECURRING(), $plan->getType());
        $this->assertTrue($plan->isType(PlanType::RECURRING()));
        $this->assertSame('recurring_application_charge', $plan->getTypeApiString());
        $this->assertSame('recurring_application_charges', $plan->getTypeApiString(true));
        $this->assertFalse($plan->hasTrial());
        $this->assertFalse($plan->isOnInstall());
        $this->assertFalse($plan->isTest());
    }
}
