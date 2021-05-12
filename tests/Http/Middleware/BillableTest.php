<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Auth\AuthManager;
use Osiset\ShopifyApp\Http\Middleware\Billable as BillableMiddleware;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;

class BillableTest extends TestCase
{
    /**
     * @var AuthManager
     */
    protected $auth;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->app->make(AuthManager::class);
    }

    public function testEnabledBillingWithUnpaidShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->create();
        $this->auth->login($shop);
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runMiddleware(BillableMiddleware::class);

        // Assert it was not called and redirect happened
        $this->assertFalse($result[0]);
        $this->assertNotFalse(strpos($result[1], 'Redirecting to http://localhost/billing'));
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

        $this->auth->login($shop);
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runMiddleware(BillableMiddleware::class);

        // Assert it was called
        $this->assertTrue($result[0]);
    }

    public function testEnabledBillingWithGrandfatheredShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->states('grandfathered')->create();
        $this->auth->login($shop);
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runMiddleware(BillableMiddleware::class);

        // Assert it was called
        $this->assertTrue($result[0]);
    }

    public function testEnabledBillingWithFreemiumShop(): void
    {
        // Enable billing and set a shop
        $shop = factory($this->model)->states('freemium')->create();
        $this->auth->login($shop);
        $this->app['config']->set('shopify-app.billing_enabled', true);

        // Run the middleware
        $result = $this->runMiddleware(BillableMiddleware::class);

        // Assert it was called
        $this->assertTrue($result[0]);
    }

    public function testDisabledBillingShouldPassOn(): void
    {
        // Ensure billing is disabled and set a shop
        $shop = factory($this->model)->create();
        $this->auth->login($shop);
        $this->app['config']->set('shopify-app.billing_enabled', false);

        // Run the middleware
        $result = $this->runMiddleware(BillableMiddleware::class);

        $this->assertTrue($result[0]);
    }
}
