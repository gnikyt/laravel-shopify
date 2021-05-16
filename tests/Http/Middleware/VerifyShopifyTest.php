<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Exceptions\HttpException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Http\Middleware\VerifyShopify;
use Osiset\ShopifyApp\Test\TestCase;

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

        // Run the middleware
        $this->runMiddleware(VerifyShopify::class, $newRequest);
    }

    public function testSkipAuthenticateAndBillingRoutes(): void
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

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertTrue($result[0]);
    }

    public function testMissingToken(): void
    {
        // Create a shop
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        // Setup the request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            ['shop' => $shop->name],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            null
        );

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result[0]);
    }

    public function testMissingTokenAjax(): void
    {
        $this->expectException(HttpException::class);

        // Create a shop
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

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
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'HTTP_X-Shop-Domain'    => $shop->name,
            ]
        );

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result[0]);
    }

    public function testTokenProcessingAndLoginShop(): void
    {
        // Create a shop that matches the token from buildToken
        factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

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
            [
                'HTTP_Authorization'    => "Bearer {$this->buildToken()}",
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertTrue($result[0]);
    }

    public function testTokenProcessingAndNotInstalledShop(): void
    {
        // Setup the request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'token' => $this->buildToken(),
                'shop'  => 'non-existent.myshopify.com',
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

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result[0]);
    }

    public function testTokenProcessingAndNotInstalledShopAjax(): void
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
            [
                'HTTP_Authorization'    => "Bearer {$this->buildToken()}",
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result);
    }

    public function testInvalidToken(): void
    {
        // Setup the request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            ['token' => $this->buildToken().'OOPS'],
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

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result[0]);
    }

    public function testInvalidTokenAjax(): void
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
            [
                'HTTP_Authorization'    => "Bearer {$this->buildToken()}OOPS",
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertFalse($result[0]);
    }

    public function testTokenProcessingAndMissMatchingShops(): void
    {
        // Create a shop that matches the token from buildToken
        factory($this->model)->create(['name' => 'shop-name.myshopify.com']);
        factory($this->model)->create(['name' => 'some-other-shop.myshopify.com']);

        // Setup the request
        $token = $this->buildToken();
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
            [
                'HTTP_Authorization'    => "Bearer {$token}",
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );
        Request::swap($newRequest);

        // Run the middleware
        $result = $this->runMiddleware(VerifyShopify::class, $newRequest);
        $this->assertTrue($result[0]);

        // Run the middleware and change the shop
        $token = $this->buildToken(['dest' => 'https://some-other-shop.myshopify.com', 'iss' => 'https://some-other-shop.myshopify.com/admin']);
        $newRequest = $newRequest->duplicate(
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
            [
                'HTTP_Authorization'    => "Bearer {$token}",
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $this->expectException(HttpException::class);
        $this->runMiddleware(VerifyShopify::class, $newRequest);
    }
}
