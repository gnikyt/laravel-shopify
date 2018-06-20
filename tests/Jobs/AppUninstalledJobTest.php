<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Jobs\AppUninstalledJob;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;
use ReflectionObject;

class AppUninstalledJobJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        // Re-used variables
        $this->shop = Shop::find(1);
        $this->data = json_decode(file_get_contents(__DIR__.'/../fixtures/app_uninstalled.json'));
    }

    public function testJobAcceptsLoad()
    {
        $job = new AppUninstalledJob($this->shop->shopify_domain, $this->data);

        $refJob = new ReflectionObject($job);
        $refData = $refJob->getProperty('data');
        $refData->setAccessible(true);
        $refShopDomain = $refJob->getProperty('shopDomain');
        $refShopDomain->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->shop->shopify_domain, $refShopDomain->getValue($job));
        $this->assertEquals($this->data, $refData->getValue($job));
        $this->assertEquals($this->shop->shopify_domain, $refShop->getValue($job)->shopify_domain);
    }

    public function testJobSoftDeletesShopAndCharges()
    {
        // Create a new charge to test against
        $charge = new Charge();
        $charge->charge_id = 987654321;
        $charge->test = false;
        $charge->name = 'Base Plan Dummy';
        $charge->status = 'active';
        $charge->type = 1;
        $charge->price = 25.00;
        $charge->trial_days = 0;
        $charge->shop_id = $this->shop->id;
        $charge->created_at = Carbon::now()->addDays(1); // Test runs too fast to make "latest" work
        $charge->save();

        // Ensure shop is not trashed, and has charges
        $this->shop->refresh();
        $this->assertFalse($this->shop->trashed());
        $this->assertEquals(true, $this->shop->hasCharges());

        // Run the job
        $job = new AppUninstalledJob($this->shop->shopify_domain, $this->data);
        $result = $job->handle();

        // Refresh both models to see the changes
        $this->shop->refresh();
        $lastCharge = $this->shop->charges()
            ->withTrashed()
            ->where(function ($query) {
                $query->latestByType(Charge::CHARGE_RECURRING);
            })->orWhere(function ($query) {
                $query->latestByType(Charge::CHARGE_ONETIME);
            })->latest();

        // Confirm job worked...
        $this->assertEquals(true, $result);
        $this->assertEquals(true, $this->shop->trashed());
        $this->assertFalse($this->shop->hasCharges());
        $this->assertEquals($charge->charge_id, $lastCharge->charge_id);
        $this->assertEquals('cancelled', $lastCharge->status);
    }

    public function testJobDoesNothingForUnknownShop()
    {
        $job = new AppUninstalledJob('unknown-shop.myshopify.com', null);
        $this->assertEquals(false, $job->handle());
    }
}
