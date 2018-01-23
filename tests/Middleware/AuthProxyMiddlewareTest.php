<?php namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\AuthProxy;
use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Input;

class AuthProxyMiddlewareTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // From Shopify's docs
        $this->queryParams = [
            'extra' => ['1', '2'],
            'shop' => 'shop-name.myshopify.com',
            'path_prefix' => '/apps/awesome_reviews',
            'timestamp' => '1317327555',
            'signature' => 'a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3'
        ];

        // Set the app secret to match Shopify's docs
        config(['shopify-app.api_secret' => 'hush']);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Invalid proxy signature
     */
    public function testDenysForMissingShop()
    {
        $query = $this->queryParams;
        unset($query['shop']);
        Input::merge($query);

        $called = false;
        (new AuthProxy)->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertEquals(false, $called);
    }

    public function testRuns()
    {
        Input::merge($this->queryParams);

        $called = false;
        (new AuthProxy)->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertEquals(true, $called);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Invalid proxy signature
     */
    public function testDoesNotRunForInvalidSignature()
    {
        $query = $this->queryParams;
        $query['oops'] = 'i-did-it-again';
        Input::merge($query);

        $called = false;
        (new AuthProxy)->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertEquals(false, $called);
    }
}
