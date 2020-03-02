<?php

namespace OhMyBrew\ShopifyApp\Test\Messaging\Jobs;

use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlan;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Messaging\Jobs\AppUninstalledJob;

class AppUninstalledTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSoftDeletesShopAndCharges()
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create a shop attached to the plan
        $shop = factory($this->model)->create(['plan_id' => $plan->getId()->toNative()]);

        // Create a charge for the shop and plan
        factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->getId()->toNative(),
            'user_id' => $shop->getId()->toNative(),
            'status'  => ChargeStatus::ACTIVE()->toNative(),
        ]);

        // Ensure shop is not trashed, and has charges
        $this->assertFalse($shop->trashed());
        $this->assertTrue($shop->hasCharges());
        $this->assertNotNull($shop->plan);
        $this->assertNotEmpty($shop->password);

        // Run the job
        AppUninstalledJob::dispatchNow(
            $shop->getId(),
            json_decode(file_get_contents(__DIR__.'/../../fixtures/app_uninstalled.json')),
            $this->app->make(CancelCurrentPlan::class)
        );

        // Refresh both models to see the changes
        $shop->refresh();

        // Confirm job worked...
        $this->assertTrue($shop->trashed());
        $this->assertFalse($shop->hasCharges());
        $this->assertNull($shop->plan);
        $this->assertEmpty($shop->password);
    }
}
