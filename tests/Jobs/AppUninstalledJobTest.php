<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

use OhMyBrew\ShopifyApp\Jobs\AppUninstalledJob;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionObject;

class AppUninstalledJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        // Get the data
        $this->data = json_decode(file_get_contents(__DIR__.'/../fixtures/app_uninstalled.json'));
    }

    public function testJobAcceptsLoad()
    {
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create(['plan_id' => $plan->id]);

        $job = new AppUninstalledJob($shop->shopify_domain, $this->data);

        $refJob = new ReflectionObject($job);
        $refData = $refJob->getProperty('data');
        $refData->setAccessible(true);
        $refShopDomain = $refJob->getProperty('shopDomain');
        $refShopDomain->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($shop->shopify_domain, $refShopDomain->getValue($job));
        $this->assertEquals($this->data, $refData->getValue($job));
        $this->assertEquals($shop->shopify_domain, $refShop->getValue($job)->shopify_domain);
    }

    public function testJobSoftDeletesShopAndCharges()
    {
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create(['plan_id' => $plan->id]);
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
            'status'  => 'active',
        ]);

        // Ensure shop is not trashed, and has charges
        $this->assertFalse($shop->trashed());
        $this->assertEquals(true, $shop->hasCharges());

        // Run the job
        $job = new AppUninstalledJob($shop->shopify_domain, $this->data);
        $result = $job->handle();

        // Refresh both models to see the changes
        $shop->refresh();
        $lastCharge = $shop
            ->charges()
            ->withTrashed()
            ->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])
            ->where('plan_id', $plan->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Confirm job worked...
        $this->assertEquals(true, $result);
        $this->assertEquals(true, $shop->trashed());
        $this->assertFalse($shop->hasCharges());
        $this->assertEquals('cancelled', $lastCharge->status);
        $this->assertNull($shop->plan);
        $this->assertNull($shop->shopify_token);
    }

    public function testJobDoesNothingForUnknownShop()
    {
        $job = new AppUninstalledJob('unknown-shop.myshopify.com', null);
        $this->assertFalse($job->handle());
    }
}
