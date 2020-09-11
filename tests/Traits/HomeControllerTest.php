<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Services\ShopSession;

class HomeControllerTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ShopSession
     */
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();

        // Shop session helper
        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testHomeRouteWithAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $response = $this->get('/');
        $response->assertStatus(200);

        $this->assertTrue(strpos($response->content(), "apiKey: ''") !== false);
        $this->assertTrue(strpos($response->content(), "shopOrigin: '{$shop->name}'") !== false);
    }

    public function testHomeRouteWithNoAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Turn off AppBridge
        $this->app['config']->set('shopify-app.appbridge_enabled', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(strpos($response->content(), '@shopify'));
    }
}