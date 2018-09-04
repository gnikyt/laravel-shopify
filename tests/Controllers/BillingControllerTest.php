<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\Controllers\BillingController;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);

        // Base shop for all tests here
        $this->shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        session(['shopify_domain' => $this->shop->shopify_domain]);
    }

    public function testSendsShopToBillingScreen()
    {
        $response = $this->get('/billing');
        $response->assertViewHas(
            'url',
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f'
        );
    }

    public function testShopAcceptsBilling()
    {
        // Use the base shop
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $chargeId = 1029266947;

        // example.myshopify.com has previous charge defined in TestCase setup
        $oldCharge = $shop->charges()->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])->orderBy('created_at', 'desc')->first();

        // Run with a new charge
        $response = $this->call('get', '/billing/process/1', ['charge_id' => $chargeId]);

        // Get the new charge and refresh the old one
        $newCharge = $shop->charges()->get()->last();
        $oldCharge->refresh();

        $response->assertStatus(302);
        $this->assertEquals($chargeId, $newCharge->charge_id);
        $this->assertEquals('cancelled', $oldCharge->status);
    }

    public function testShopDeclinesBilling()
    {
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $response = $this->call('get', '/billing/process/1', ['charge_id' => 10292]);
        $lastCharge = $shop->charges()->get()->last();

        $response->assertStatus(403);
        $this->assertEquals(10292, $lastCharge->charge_id);
        $this->assertEquals('declined', $lastCharge->status);
        $this->assertEquals(
            'It seems you have declined the billing charge for this application',
            $response->exception->getMessage()
        );
    }

    public function testReturnOnInstallFlaggedPlan()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getPlan');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(
            Plan::find(1),
            $method->invoke($controller, null)
        );
    }

    public function testReturnPlanPassedToController()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getPlan');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(Plan::find(2), $method->invoke($controller, 2));
    }

    public function testReturnsLastChargeForShop()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getLastCharge');
        $method->setAccessible(true);

        // Based on default config
        $this->assertInstanceOf(Charge::class, $method->invoke($controller, $this->shop));
    }
}
