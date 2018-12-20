<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use Illuminate\Database\Eloquent\Collection;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class PlanModelTest extends TestCase
{
    public function testReturnsChargesForPlan()
    {
        $shop = factory(Shop::class)->create();
        $plan = factory(Plan::class)->states('type_recurring')->create();
        factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
        ]);

        $charges = $plan->charges;

        $this->assertInstanceOf(Collection::class, $charges);
        $this->assertTrue(count($charges) > 0);
    }

    public function testReturnsTypeAsString()
    {
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $plan_2 = factory(Plan::class)->states('type_onetime')->create();

        $this->assertEquals('recurring_application_charge', $plan->typeAsString());
        $this->assertEquals('recurring_application_charges', $plan->typeAsString(true));
        $this->assertEquals('application_charge', $plan_2->typeAsString());
        $this->assertEquals('application_charges', $plan_2->typeAsString(true));
    }

    public function testPlanHasTrial()
    {
        $plan = factory(Plan::class)->states('type_recurring', 'trial')->create();
        $plan_2 = factory(Plan::class)->states('type_recurring')->create();

        $this->assertFalse($plan_2->hasTrial());
        $this->assertTrue($plan->hasTrial());
    }

    public function testPlanOnInstallFlag()
    {
        $plan = factory(Plan::class)->states('type_recurring', 'installable')->create();
        $plan_2 = factory(Plan::class)->states('type_recurring')->create();

        $this->assertFalse($plan_2->isOnInstall());
        $this->assertTrue($plan->isOnInstall());
    }

    public function testPlanIsTest()
    {
        $plan = factory(Plan::class)->states('type_recurring', 'test')->create();
        $plan_2 = factory(Plan::class)->states('type_recurring')->create();

        $this->assertTrue($plan->isTest());
        $this->assertFalse($plan_2->isTest());
    }
}
