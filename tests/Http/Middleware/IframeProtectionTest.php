<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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
        $header = "frame-ancestors https://$domain https://admin.shopify.com";

        Http::fake([
            route('home') => Http::response([], 200, [
                'Content-Security-Policy' => "frame-ancestors https://$domain https://admin.shopify.com",
            ]),
        ]);

        $response = Http::get(route('home'));

        $this->assertNotEmpty($response->getHeader('content-security-policy'));
        $this->assertEquals("frame-ancestors https://$domain https://admin.shopify.com", $header);
    }

    public function testIframeProtectionWithUnauthorizedShop(): void
    {
        $request = new Request();
        $next = function () {
            return new Response('Test Response');
        };

        $middleware = new IframeProtection(new ShopQuery());
        $response = $middleware->handle($request, $next);

        $header = $response->headers->get('content-security-policy');

        $this->assertNotEmpty($header);
        $this->assertEquals('frame-ancestors https://*.myshopify.com https://admin.shopify.com', $header);
    }
}
