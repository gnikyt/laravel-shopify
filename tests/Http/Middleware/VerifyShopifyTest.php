<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Closure;
use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Exceptions\HttpException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Http\Middleware\VerifyShopify;

class VerifyShopifyTest extends TestCase
{
    public function testHmacFail(): void
    {
        $this->expectException(SignatureVerificationException::class);

        // Setup request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '9f4d79eb5ab1806c390b3dda0bfc7be714a92df165d878f22cf3cc8145249ca8',
                'timestamp' => 'oops',
                'code'      => 'oops',
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            []
        );
        Request::swap($newRequest);

        // Run the middleware
        $this->runAuth();
    }

    public function testSkipAuthenticateRoutes(): void
    {
        // Setup the request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            ['REQUEST_URI' => '/authenticate']
        );
        Request::swap($newRequest);

        // Run the middleware
        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testMissingToken(): void
    {
        // Run the middleware
        $result = $this->runAuth();
        $this->assertFalse($result);
    }

    public function testMissingTokenAjax(): void
    {
        $this->expectException(HttpException::class);

        // Setup the request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        Request::swap($newRequest);

        // Run the middleware
        $result = $this->runAuth();
        $this->assertFalse($result);
    }

    private function runAuth(Closure $cb = null, $requestInstance = null): bool
    {
        $called = false;
        $requestInstance = $requestInstance ?? Request::instance();
        ($this->app->make(VerifyShopify::class))->handle($requestInstance, function ($request) use (&$called, $cb) {
            $called = true;
            if ($cb) {
                $cb($request);
            }
        });

        return $called;
    }
}
