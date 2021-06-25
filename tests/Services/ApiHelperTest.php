<?php

namespace Osiset\ShopifyApp\Test\Services;

use Exception;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Enums\PlanInterval;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

class ApiHelperTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Contracts\ApiHelper
     */
    protected $api;

    public function setUp(): void
    {
        parent::setUp();

        $this->api = $this->app->make(IApiHelper::class);
    }

    public function testMake(): void
    {
        // Cover the full make
        $this->app['config']->set('shopify-app.api_rate_limiting_enabled', true);

        // Make it
        $api = $this->api->make()->getApi();

        $this->assertInstanceOf(BasicShopifyAPI::class, $api);
        $this->assertSame(Util::getShopifyConfig('api_secret'), $this->app['config']->get('shopify-app.api_secret'));
        $this->assertSame(Util::getShopifyConfig('api_key'), $this->app['config']->get('shopify-app.api_key'));
        $this->assertSame($this->app['config']->get('shopify-app.api_version'), '2020-01');
    }

    public function testSetAndGetApi(): void
    {
        // Make it and set it
        $api = $this->api->make();
        $this->api->setApi($api->getApi());

        $this->assertInstanceOf(BasicShopifyAPI::class, $this->api->getApi());
    }

    public function testWithApi(): void
    {
        // Make it and set it
        $api = $this->api->make();

        // Use it
        $called = false;
        $this->api->withApi($api->getApi(), function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testBuildAuthUrl(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        $this->assertNotEmpty(
            $shop->apiHelper()->buildAuthUrl(AuthMode::OFFLINE(), 'read_content')
        );
    }

    public function testGetScriptTags(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_script_tags']);

        $data = $shop->apiHelper()->getScriptTags();
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('onload', $data[0]['event']);
        $this->assertCount(2, $data);
    }

    public function testCreateScriptTags(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        $data = $shop->apiHelper()->createScriptTag([]);
        $this->assertInstanceOf(ResponseAccess::class, $data);
    }

    public function testDeleteScriptTag(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        $this->assertInstanceOf(
            ResponseAccess::class,
            $shop->apiHelper()->deleteScriptTag(1)
        );
    }

    public function testGetCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_application_charge']);

        $data = $shop->apiHelper()->getCharge(ChargeType::CHARGE(), ChargeReference::fromNative(1234));
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('iPod Cleaning', $data->name);
        $this->assertSame('accepted', $data['status']);
    }

    public function testActivateCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges_activate']);

        $data = $shop->apiHelper()->activateCharge(ChargeType::RECURRING(), ChargeReference::fromNative(1234));
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('Super Mega Plan', $data['name']);
    }

    public function testCreateCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges']);

        // Build the details object
        $transfer = new PlanDetailsTransfer();
        $transfer->name = 'Test';
        $transfer->price = 12.00;
        $transfer->interval = PlanInterval::EVERY_30_DAYS()->toNative();
        $transfer->test = true;
        $transfer->trialDays = 7;

        $data = $shop->apiHelper()->createCharge(
            ChargeType::RECURRING(),
            $transfer
        );
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('Basic Plan', $data['name']);
    }

    public function testGetWebhooks(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_webhooks']);

        $data = $shop->apiHelper()->getWebhooks();
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertTrue(count($data) > 0);
    }

    public function testCreateWebhook(): void
    {
        // Create a shop
        /** @var IShopModel $shop */
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_webhook']);

        $data = $shop->apiHelper()->createWebhook([
            'topic'   => 'ORDERS_CREATE',
            'address' => 'https://localhost/webhook/orders-create',
        ]);
        $this->assertInstanceOf(ResponseAccess::class, $data);
        $this->assertSame('ORDERS_CREATE', $data['data']['webhookSubscriptionCreate']['topic']);
    }

    public function testDeleteWebhook(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        $this->assertInstanceOf(
            ResponseAccess::class,
            $shop->apiHelper()->deleteWebhook(1)
        );
    }

    public function testCreateUsageCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges_usage_charges']);

        $transfer = new UsageChargeDetailsTransfer();
        $transfer->chargeReference = ChargeReference::fromNative(1);
        $transfer->price = 12.00;
        $transfer->description = 'Hello!';

        $data = $shop->apiHelper()->createUsageCharge($transfer);
        $this->assertInstanceOf(ResponseAccess::class, $data);
    }

    public function testErrors(): void
    {
        $this->expectExceptionObject(new Exception('Error!', 0));

        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty_with_error_graphql']);

        $transfer = new PlanDetailsTransfer();
        $transfer->name = 'Test';
        $transfer->price = 12.00;
        $transfer->interval = PlanInterval::ANNUAL()->toNative();
        $transfer->test = true;
        $transfer->trialDays = 7;

        $shop->apiHelper()->createChargeGraphQL($transfer);
    }
}
