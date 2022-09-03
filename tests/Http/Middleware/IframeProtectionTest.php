<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Osiset\ShopifyApp\Http\Middleware\IframeProtection;
use Osiset\ShopifyApp\Storage\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Test\TestCase;

class IframeProtectionTest extends TestCase
{
    /**
     * @var AuthManager
     */
    protected $auth;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->app->make(AuthManager::class);
    }

    public function testIframeProtectionWithAuthorizedShop(): void
    {
        $shop = factory($this->model)->create();
        $this->auth->login($shop);

        $domain = auth()->user()->name;
        $expectedHeader = "frame-ancestors https://$domain https://admin.shopify.com";

        $request = new Request();
        $shopQueryStub = $this->createStub(ShopQuery::class);
        $shopQueryStub->method('getByDomain')->willReturn($shop);
        $next = function () {
            return new Response('Test Response');
        };

        $middleware = new IframeProtection($shopQueryStub);
        $response = $middleware->handle($request, $next);
        $currentHeader = $response->headers->get('content-security-policy');

        $this->assertNotEmpty($currentHeader);
        $this->assertEquals($expectedHeader, $currentHeader);
    }

    public function testIframeProtectionWithUnauthorizedShop(): void
    {
        $expectedHeader = 'frame-ancestors https://*.myshopify.com https://admin.shopify.com';

        $request = new Request();
        $shopQuery = new ShopQuery();
        $next = function () {
            return new Response('Test Response');
        };

        $middleware = new IframeProtection($shopQuery);
        $response = $middleware->handle($request, $next);
        $currentHeader = $response->headers->get('content-security-policy');

        $this->assertNotEmpty($currentHeader);
        $this->assertEquals($expectedHeader, $currentHeader);
    }
}
