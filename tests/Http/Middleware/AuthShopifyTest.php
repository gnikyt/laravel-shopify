<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Http\Middleware\AuthShopify as AuthShopifyMiddleware;

class AuthShopifyTest extends TestCase
{
    public function testBase(): void
    {
        $this->runAuth();
        $this->assertTrue(true);
    }

    public function testQueryInput(): void
    {
        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '9f4d79eb5ab1806c390b3dda0bfc7be714a92df165d878f22cf3cc8145249ca8',
                'timestamp' => '1565631587',
                'code'      => '123',
                'locale'    => 'de',
                'state'     => '3.14',
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
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=xyz.com',
            ])
        );

        Request::swap($newRequest);

        $this->runAuth();
        $this->assertTrue(true);
    }

    public function testHmacFail(): void
    {
        $this->expectException(SignatureVerificationException::class);

        // Run the middleware
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
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=xyz.com',
            ])
        );

        Request::swap($newRequest);

        $this->runAuth();
    }

    public function testReferer()
    {
        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            null,
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163&timestamp=1337178173&code=1234678',
            ])
        );

        Request::swap($newRequest);

        $this->runAuth();
        $this->assertTrue(true);
    }

    public function testHeaders()
    {
        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            null,
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // Referer with no query params
            array_merge(Request::server(), [
                'Referer' => '',
            ])
        );

        $newRequest->headers->set('X-Shop-Domain', 'example.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163');
        $newRequest->headers->set('X-Shop-Time', '1337178173');
        $newRequest->headers->set('X-Shop-Code', '1234678');

        Request::swap($newRequest);

        $this->runAuth();
        $this->assertTrue(true);
    }

    private function runAuth(Closure $cb = null, $requestInstance = null): void
    {//$this->expectedException();
        ($this->app->make(AuthShopifyMiddleware::class))->handle($requestInstance ? $requestInstance : Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });
    }
}