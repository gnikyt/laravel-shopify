<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ChargeModelTest extends TestCase
{
    public function testBelongsToShop()
    {
        $this->assertInstanceOf(
            Shop::class,
            Charge::find(1)->shop
        );
    }

    public function testChargeImplementsType()
    {
        $this->assertEquals(
            Charge::CHARGE_RECURRING,
            Charge::find(1)->type
        );
    }

    public function testScopeLatest()
    {
        $this->assertEquals(
            get_class(Charge::latest()),
            Charge::class
        );
    }

    public function testScopeLatestByType()
    {
        $this->assertEquals(
            get_class(Charge::latestByType(Charge::CHARGE_RECURRING)),
            Charge::class
        );
    }

    public function testIsTest()
    {
        $this->assertEquals(true, Charge::find(1)->isTest());
    }

    public function testIsType()
    {
        $this->assertTrue(Charge::find(1)->isType(Charge::CHARGE_RECURRING));
    }

    public function testIsTrial()
    {
        $this->assertTrue(Charge::find(1)->isTrial());
        $this->assertFalse(Charge::find(4)->isTrial());
    }

    public function testIsActiveTrial()
    {
        $this->assertTrue(Charge::find(2)->isActiveTrial());
        $this->assertFalse(Charge::find(4)->isActiveTrial());
    }

    public function testRemainingTrialDays()
    {
        $this->assertEquals(0, Charge::find(1)->remainingTrialDays());
        $this->assertEquals(2, Charge::find(2)->remainingTrialDays());
        $this->assertEquals(0, Charge::find(3)->remainingTrialDays());
        $this->assertNull(Charge::find(4)->remainingTrialDays());
    }

    public function testUsedTrialDays()
    {
        $this->assertEquals(7, Charge::find(1)->usedTrialDays());
        $this->assertEquals(5, Charge::find(2)->usedTrialDays());
        $this->assertEquals(7, Charge::find(3)->usedTrialDays());
        $this->assertNull(Charge::find(4)->usedTrialDays());
    }

    public function testAcceptedAndDeclined()
    {
        $this->assertTrue(Charge::find(1)->wasAccepted());
        $this->assertTrue(!Charge::find(1)->wasDeclined());
    }
}
