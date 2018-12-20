<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ChargeModelTest extends TestCase
{
    public function testBelongsToShop()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertInstanceOf(Shop::class, $charge->shop);
    }

    public function testBelongsToPlan()
    {
        $shop = factory(Shop::class)->create();
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
        ]);

        $this->assertInstanceOf(Plan::class, $charge->plan);
    }

    public function testIsTest()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring', 'test')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertTrue($charge->isTest());
    }

    public function testIsType()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertTrue($charge->isType(Charge::CHARGE_RECURRING));
    }

    public function testIsTrial()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring', 'trial')->create([
            'shop_id' => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertTrue($charge->isTrial());
        $this->assertFalse($charge_2->isTrial());
    }

    public function testIsActiveTrial()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring', 'trial')->create([
            'shop_id'       => $shop->id,
            'trial_ends_on' => Carbon::today()->addDays(1),
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring', 'trial')->create([
            'shop_id'       => $shop->id,
            'trial_ends_on' => Carbon::today()->subDays(1),
        ]);

        $this->assertTrue($charge->isActiveTrial());
        $this->assertFalse($charge_2->isActiveTrial());
    }

    public function testRemainingTrialDays()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring', 'trial')->create([
            'shop_id'       => $shop->id,
            'trial_ends_on' => Carbon::today(),
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring', 'trial')->create([
            'shop_id'       => $shop->id,
            'trial_ends_on' => Carbon::today()->addDays(1),
        ]);
        $charge_3 = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertEquals(0, $charge->remainingTrialDays());
        $this->assertEquals(1, $charge_2->remainingTrialDays());
        $this->assertNull($charge_3->remainingTrialDays());
    }

    public function testUsedTrialDays()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'shop_id'       => $shop->id,
            'trial_days'    => 5,
            'trial_ends_on' => Carbon::today(),
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'shop_id'       => $shop->id,
            'trial_days'    => 5,
            'trial_ends_on' => Carbon::today()->addDays(1),
        ]);
        $charge_3 = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);

        $this->assertEquals(5, $charge->usedTrialDays());
        $this->assertEquals(4, $charge_2->usedTrialDays());
        $this->assertNull($charge_3->usedTrialDays());
    }

    public function testAcceptedAndDeclined()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_ACCEPTED,
            'shop_id' => $shop->id,
        ]);

        $this->assertTrue($charge->isAccepted());
        $this->assertFalse($charge->isDeclined());
    }

    public function testActive()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_ACTIVE,
            'shop_id' => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_CANCELLED,
            'shop_id' => $shop->id,
        ]);

        $this->assertFalse($charge_2->isActive());
        $this->assertTrue($charge->isActive());
    }

    public function testOngoing()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_CANCELLED,
            'shop_id' => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_ACTIVE,
            'shop_id' => $shop->id,
        ]);

        $this->assertFalse($charge->isOngoing());
        $this->assertTrue($charge_2->isOngoing());
    }

    public function testCancelled()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_CANCELLED,
            'shop_id' => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_ACTIVE,
            'shop_id' => $shop->id,
        ]);

        $this->assertFalse($charge_2->isCancelled());
        $this->assertTrue($charge->isCancelled());
    }

    public function testRemainingTrialDaysFromCancel()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'trial_days'    => 5,
            'trial_ends_on' => Carbon::today()->subDays(1),
            'cancelled_on'  => Carbon::today()->subDays(3),
            'status'        => 'cancelled',
            'shop_id'       => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'trial_days'    => 5,
            'trial_ends_on' => Carbon::today()->subDays(1),
            'cancelled_on'  => Carbon::today()->subDays(1),
            'status'        => 'cancelled',
            'shop_id'       => $shop->id,
        ]);
        $charge_3 = factory(Charge::class)->states('type_recurring')->create([
            'cancelled_on'  => Carbon::today()->subDays(1),
            'status'        => 'cancelled',
            'shop_id'       => $shop->id,
        ]);
        $charge_4 = factory(Charge::class)->states('type_recurring')->create([
            'trial_days'    => 5,
            'trial_ends_on' => Carbon::today()->subDays(1),
            'status'        => 'active',
            'shop_id'       => $shop->id,
        ]);

        $this->assertEquals(2, $charge->remainingTrialDaysFromCancel());
        $this->assertEquals(0, $charge_2->remainingTrialDaysFromCancel());
        $this->assertNull($charge_3->remainingTrialDaysFromCancel());
        $this->assertEquals(0, $charge_4->remainingTrialDaysFromCancel());
    }

    public function testRetreieve()
    {
        // Stub the API
        Config::set('shopify-app.api_class', new ApiStub());
        ApiStub::stubResponses([
            'get_application_charge',
            'get_recurring_application_charge_activate',
            'get_application_credit',
        ]);

        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_onetime')->create([
            'shop_id' => $shop->id,
        ]);
        $charge_2 = factory(Charge::class)->states('type_recurring')->create([
            'shop_id' => $shop->id,
        ]);
        $charge_3 = factory(Charge::class)->states('type_credit')->create([
            'shop_id' => $shop->id,
        ]);

        $result = $charge->retrieve();
        $result_2 = $charge_2->retrieve();
        $result_3 = $charge_3->retrieve();

        // Assert we get an object
        $this->assertTrue(is_object($result));
        $this->assertTrue(is_object($result_2));
        $this->assertTrue(is_object($result_3));
    }

    public function testCancel()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'status'  => Charge::STATUS_ACTIVE,
            'shop_id' => $shop->id,
        ]);
        $charge->cancel();

        $this->assertEquals(Charge::STATUS_CANCELLED, $charge->status);
    }

    /**
     * @expectedException Exception
     */
    public function testCancelError()
    {
        $shop = factory(Shop::class)->create();
        $charge = factory(Charge::class)->states('type_usage')->create([
            'shop_id' => $shop->id,
        ]);
        $charge->cancel();
    }
}
