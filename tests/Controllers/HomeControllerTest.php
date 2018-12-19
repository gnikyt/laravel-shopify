<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());

        // Base shop for all tests here
        Session::put('shopify_domain', 'example.myshopify.com');
    }

    public function testHomeRouteWithESDK()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $this->assertTrue(strpos($response->content(), "apiKey: ''") !== false);
        $this->assertTrue(strpos($response->content(), "shopOrigin: 'https://example.myshopify.com'") !== false);
    }

    public function testHomeRouteWithNoESDK()
    {
        // Tuen off ESDK
        Config::set('shopify-app.esdk_enabled', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(strpos($response->content(), 'ShopifyApp.init'));
    }
}
