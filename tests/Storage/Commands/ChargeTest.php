<?php

namespace Osiset\ShopifyApp\Test\Storage\Commands;

use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Objects\Values\PlanId;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use Osiset\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;

class ChargeTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = $this->app->make(IChargeCommand::class);
    }

    public function testMake(): void
    {
        // Make a charge
        $this->assertInstanceOf(
            ChargeId::class,
            $this->seedData()
        );
    }

    public function testDelete(): void
    {
        // Make a charge
        $this->seedData();

        $this->assertTrue(
            $this->command->delete(new ChargeReference(123456), new ShopId(1))
        );
    }

    public function testMakeUsage(): void
    {
        // Create details transfer
        $ud = new UsageChargeDetailsTransfer();
        $ud->price = 12.00;
        $ud->description = 'Test';
        $ud->chargeReference = new ChargeReference(123456);

        // Create usage charge transfer
        $uc = new UsageChargeTransfer();
        $uc->shopId = new ShopId(1);
        $uc->chargeReference = new ChargeReference(12345678);
        $uc->billingOn = Carbon::today();
        $uc->details = $ud;

        $this->assertInstanceOf(
            ChargeId::class,
            $this->command->makeUsage($uc)
        );
    }

    public function testCancel(): void
    {
        // Make a charge
        $this->seedData();

        $this->assertTrue(
            $this->command->cancel(new ChargeReference(123456))
        );
    }

    protected function seedData(): ChargeId
    {
        // Make the plan details object
        $planDetails = new PlanDetailsTransfer();
        $planDetails->name = 'Test Plan';
        $planDetails->price = 12.00;
        $planDetails->test = true;
        $planDetails->trialDays = 7;
        $planDetails->cappedAmount = null;
        $planDetails->cappedTerms = null;

        // Make the transfer object
        $charge = new ChargeTransfer();
        $charge->shopId = new ShopId(1);
        $charge->chargeReference = new ChargeReference(123456);
        $charge->chargeType = ChargeType::RECURRING();
        $charge->chargeStatus = ChargeStatus::ACCEPTED();
        $charge->planDetails = $planDetails;
        $charge->planId = new PlanId(1);

        return $this->command->make($charge);
    }
}