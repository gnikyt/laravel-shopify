<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use Illuminate\Database\Eloquent\Collection;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Test\TestCase;

class PlanModelTest extends TestCase
{
    public function testReturnsChargesForPlan()
    {
        $charges = Plan::find(1)->charges;

        $this->assertInstanceOf(Collection::class, $charges);
        $this->assertTrue(count($charges) > 0);
    }

    public function testReturnsTypeAsString()
    {
        $plan = Plan::find(1);

        $this->assertEquals('recurring_application_charge', $plan->typeAsString());
        $this->assertEquals('recurring_application_charges', $plan->typeAsString(true));
    }

    public function testPlanHasTrial()
    {
        $this->assertFalse(Plan::find(2)->hasTrial());
        $this->assertTrue(Plan::find(1)->hasTrial());
    }

    public function testPlanOnInstallFlag()
    {
        $this->assertFalse(Plan::find(2)->isOnInstall());
        $this->assertTrue(Plan::find(1)->isOnInstall());
    }

    public function testPlanIsTest()
    {
        $this->assertTrue(Plan::find(3)->isTest());
        $this->assertFalse(Plan::find(1)->isTest());
    }
}
