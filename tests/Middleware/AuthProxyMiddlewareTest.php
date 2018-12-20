<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Middleware\AuthProxy;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthProxyMiddlewareTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // From Shopify's docs
        $this->queryParams = [
            'extra'       => ['1', '2'],
            'shop'        => 'shop-name.myshopify.com',
            'path_prefix' => '/apps/awesome_reviews',
            'timestamp'   => '1317327555',
            'signature'   => 'a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3',
        ];

        // Set the app secret to match Shopify's docs
        Config::set('shopify-app.api_secret', 'hush');
    }

    public function testDenysForMissingShop()
    {
        // Remove shop from params
        $query = $this->queryParams;
        unset($query['shop']);
        Input::merge($query);

        // Run the middleware
        $result = $this->runAuthProxy();

        // Assert it was not processed and our status
        $this->assertFalse($result[1]);
        $this->assertEquals(401, $result[0]->status());
    }

    public function testRuns()
    {
        Input::merge($this->queryParams);

        // Confirm no shop
        $this->assertNull(Session::get('shopify_domain'));

        // Run the middleware
        $result = $this->runAuthProxy(function ($request) {
            // Session should be set by now
            $this->assertEquals($this->queryParams['shop'], Session::get('shopify_domain'));

            // Shop should be callable
            $shop = ShopifyApp::shop();
            $this->assertEquals($this->queryParams['shop'], $shop->shopify_domain);
        });

        // Confirm full run
        $this->assertTrue($result[1]);
    }

    public function testDoesNotRunForInvalidSignature()
    {
        // Make the signature invalid
        $query = $this->queryParams;
        $query['oops'] = 'i-did-it-again';
        Input::merge($query);

        // Run the middleware
        $result = $this->runAuthProxy();

        // Assert it was not processed and our status
        $this->assertFalse($result[1]);
        $this->assertEquals(401, $result[0]->status());
    }

    private function runAuthProxy(Closure $cb = null)
    {
        $called = false;
        $response = (new AuthProxy())->handle(Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
