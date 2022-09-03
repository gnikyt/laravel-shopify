<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Osiset\ShopifyApp\Http\Middleware\IframeProtection;
use Osiset\ShopifyApp\Storage\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

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
        $route = Util::getShopifyConfig('route_names.home');
        $shop = factory($this->model)->create();
        $this->auth->login($shop);

        $domain = auth()->user()->name;
        $expectedHeader = "frame-ancestors https://$domain https://admin.shopify.com";

        Http::fake([
            $route => Http::response([], 200, [
                'Content-Security-Policy' => "frame-ancestors https://$domain https://admin.shopify.com",
            ]),
        ]);

        $response = Http::get($route);
        $currentHeader = $response->getHeader('content-security-policy')[0];

        $this->assertNotEmpty($currentHeader);
        $this->assertEquals($expectedHeader, $currentHeader);
    }

    public function testIframeProtectionWithUnauthorizedShop(): void
    {
        $expectedHeader = 'frame-ancestors https://*.myshopify.com https://admin.shopify.com';

        $request = new Request();
        $next = function () {
            return new Response('Test Response');
        };

        $middleware = new IframeProtection(new ShopQuery());
        $response = $middleware->handle($request, $next);
        $currentHeader = $response->headers->get('content-security-policy');

        $this->assertNotEmpty($currentHeader);
        $this->assertEquals($expectedHeader, $currentHeader);
    }
}
