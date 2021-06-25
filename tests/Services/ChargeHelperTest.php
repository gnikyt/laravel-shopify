<?php

namespace Osiset\ShopifyApp\Test\Services;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails;
use Osiset\ShopifyApp\Services\ChargeHelper;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;
use stdClass;

class ChargeHelperTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ChargeHelper
     */
    protected $chargeHelper;

    public function setUp(): void
    {
        parent::setUp();

        $this->chargeHelper = $this->app->make(ChargeHelper::class);
    }

    public function testUseAndGetCharge(): void
    {
        // Seed
        $seed = $this->seedData();
        $this->chargeHelper->useCharge($seed->charge->getReference());

        $this->assertSame(
            $seed->charge->id,
            $this->chargeHelper->getCharge()->id
        );
    }

    public function testRetrieveCharge(): void
    {
        // Seed
        $seed = $this->seedData();
        $this->chargeHelper->useCharge($seed->charge->getReference());

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_application_charge']);

        $data = $this->chargeHelper->retrieve($seed->shop);
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('accepted', $data->status);
    }

    public function testTrial(): void
    {
        // Seed
        $seed = $this->seedData([
            'trial_days'    => 7,
            'trial_ends_on' => $this->now->today()->addDays(7)->format('Y-m-d'),
        ]);
        $this->chargeHelper->useCharge($seed->charge->getReference());

        $this->assertTrue($this->chargeHelper->isActiveTrial());
        $this->assertSame(7, $this->chargeHelper->remainingTrialDays());
        $this->assertSame(0, $this->chargeHelper->remainingTrialDaysFromCancel());
        $this->assertFalse($this->chargeHelper->hasExpired());
        $this->assertSame(0, $this->chargeHelper->usedTrialDays());
    }

    public function testNonTrial(): void
    {
        // Seed
        $seed = $this->seedData([
            'trial_days'    => 0,
        ]);
        $this->chargeHelper->useCharge($seed->charge->getReference());

        $this->assertFalse($this->chargeHelper->isActiveTrial());
        $this->assertNull($this->chargeHelper->remainingTrialDays());
        $this->assertNull($this->chargeHelper->remainingTrialDaysFromCancel());
        $this->assertFalse($this->chargeHelper->hasExpired());
        $this->assertNull($this->chargeHelper->usedTrialDays());
    }

    public function testTrialCancelled(): void
    {
        // Seed
        $seed = $this->seedData([
            'status'        => ChargeStatus::CANCELLED()->toNative(),
            'trial_days'    => 7,
            'trial_ends_on' => '2020-01-10',
            'cancelled_on'  => '2020-01-05',
            'expires_on'    => '2020-01-11',
        ]);
        $this->chargeHelper->useCharge($seed->charge->getReference());

        $this->assertFalse($this->chargeHelper->isActiveTrial());
        $this->assertSame(5, $this->chargeHelper->remainingTrialDaysFromCancel());
        $this->assertNull($this->chargeHelper->pastDaysForPeriod());
        $this->assertTrue($this->chargeHelper->hasExpired());
        $this->assertSame(0, $this->chargeHelper->remainingDaysForPeriod());
    }

    public function testBeginEndPeriod(): void
    {
        // Seed
        $seed = $this->seedData();
        $this->chargeHelper->useCharge($seed->charge->getReference());

        $this->assertSame(
            $this->now->today()->format('Y-m-d'),
            $this->chargeHelper->periodBeginDate()
        );
        $this->assertSame(
            $this->now->today()->addDays(30)->format('Y-m-d'),
            $this->chargeHelper->periodEndDate()
        );
        $this->assertSame(30, $this->chargeHelper->remainingDaysForPeriod());
        $this->assertSame(0, $this->chargeHelper->pastDaysForPeriod());
    }

    public function testChargeForPlan(): void
    {
        // Seed
        $seed = $this->seedData();

        $this->assertInstanceOf(
            Charge::class,
            $this->chargeHelper->chargeForPlan($seed->plan->getId(), $seed->shop)
        );
    }

    public function testDetails(): void
    {
        // Seed (trial)
        $seed = $this->seedData();
        $result = $this->chargeHelper->details($seed->plan, $seed->shop);
        $this->assertInstanceOf(PlanDetails::class, $result);

        // Seed (no trial)
        $seed = $this->seedData([], ['trial_days' => 0]);
        $result = $this->chargeHelper->details($seed->plan, $seed->shop);
        $this->assertInstanceOf(PlanDetails::class, $result);
    }

    public function testDetails2(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create([
            'trial_days' => 7,
        ]);

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        $result = $this->chargeHelper->details($plan, $shop);
        $this->assertInstanceOf(PlanDetails::class, $result);
    }

    protected function seedData($extraCharge = [], $extraPlan = [], $type = 'onetime'): stdClass
    {
        // Create a plan
        $plan = factory(Plan::class)->states("type_${type}")->create(
            array_merge(
                ['trial_days' => 7],
                $extraPlan
            )
        );

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        // Create a charge for the plan and shop
        $charge = factory(Charge::class)->states("type_${type}")->create(
            array_merge(
                [
                    'charge_id' => 12345,
                    'plan_id'   => $plan->getId()->toNative(),
                    'user_id'   => $shop->getId()->toNative(),
                ],
                $extraCharge
            )
        );

        return (object) [
            'plan'   => $plan,
            'shop'   => $shop,
            'charge' => $charge,
        ];
    }
}
