<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);

        // Shop for all tests
        session(['shopify_domain' => 'example.myshopify.com']);
    }

    public function testNoShopSessionShouldRedirectToAuthenticate()
    {
        // Kill the session
        session()->forget('shopify_domain');

        $response = $this->call('get', '/', ['shop' => 'example.myshopify.com']);
        $this->assertTrue(strpos($response->content(), 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testWithMismatchedShopsShouldRedirectToAuthenticate()
    {
        $response = $this->call('get', '/', ['shop' => 'example-different-shop.myshopify.com']);
        $this->assertTrue(strpos($response->content(), 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopWithSessionShouldLoad()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertTrue(strpos($response->content(), "apiKey: ''") !== false);
        $this->assertTrue(strpos($response->content(), "shopOrigin: 'https://example.myshopify.com'") !== false);
    }

    public function testShopWithSessionAndDisabledEsdkShouldLoad()
    {
        // Tuen off ESDK
        config(['shopify-app.esdk_enabled' => false]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(strpos($response->content(), 'ShopifyApp.init'));
    }
}
