<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

class HomeControllerTest extends TestCase
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

    public function testHomeRouteWithAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $this->call('get', '/', [], ['itp' => true])
            ->assertOk()
            ->assertSee("apiKey: '".Util::getShopifyConfig('api_key')."'", false)
            ->assertSee("shopOrigin: '{$shop->name}'", false);
    }

    public function testHomeRouteWithNoAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $this->app['config']->set('shopify-app.appbridge_enabled', false);

        $this->call('get', '/', [], ['itp' => true])
            ->assertOk()
            ->assertDontSee('@shopify');
    }
}
