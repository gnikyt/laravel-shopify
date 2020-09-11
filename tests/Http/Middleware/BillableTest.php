<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\Billable as BillableMiddleware;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;

class BillableTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ShopSession
     */
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();

        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testEnabledBillingWithUnpaidShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was not called and redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/billing') !== false);
    }

    public function testEnabledBillingWithPaidShop(): void
    {
        // Enable billing and set a shop
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);
        factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->getId()->toNative(),
            'user_id' => $shop->getId()->toNative(),
        ]);

        $this->shopSession->make($shop->getDomain());
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithGrandfatheredShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->states('grandfathered')->create();
        $this->shopSession->make($shop->getDomain());
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithFreemiumShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->states('freemium')->create();
        $this->shopSession->make($shop->getDomain());
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testDisabledBillingShouldPassOn(): void
    {
        // Ensure billing is disabled and set a shop
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());
        $this->app['config']->set('shopify-app.billing_enabled', false);

        // Run the middleware
        $result = $this->runBillable();

        $this->assertTrue($result[1]);
    }

    /**
     * @param callable|null $cb
     * @return array
     */
    private function runBillable($cb = null): array
    {
        $called = false;
        $response = ($this->app->make(BillableMiddleware::class))->handle(Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
