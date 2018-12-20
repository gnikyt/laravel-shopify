<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testHomeRouteWithESDK()
    {
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        $response = $this->get('/');
        $response->assertStatus(200);

        $this->assertTrue(strpos($response->content(), "apiKey: ''") !== false);
        $this->assertTrue(strpos($response->content(), "shopOrigin: 'https://{$shop->shopify_domain}'") !== false);
    }

    public function testHomeRouteWithNoESDK()
    {
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Turn off ESDK
        Config::set('shopify-app.esdk_enabled', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(strpos($response->content(), 'ShopifyApp.init'));
    }
}
