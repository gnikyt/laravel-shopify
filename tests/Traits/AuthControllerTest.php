<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;

class AuthControllerTest extends TestCase
{
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();

        // Setup API stub
        $this->setApiStub();

        // Shop session helper
        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testAuthRedirectsToShopifyWhenNoCode(): void
    {
        // Run the request
        $response = $this->post('/authenticate', ['shop' => 'example.myshopify.com']);

        // Check the view
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate'
        );
    }

    public function testAuthAcceptsShopWithCode(): void
    {
        // Stub the responses
        ApiStub::stubResponses(['access_token_grant']);

        // HMAC for regular tests
        $hmac = 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163';
        $hmacParams = [
            'hmac'      => $hmac,
            'shop'      => 'example.myshopify.com',
            'code'      => '1234678',
            'timestamp' => '1337178173',
        ];

        $response = $this->call('get', '/authenticate', $hmacParams);
        $response->assertRedirect();
    }

    public function testAuthThrowExceptionForBadHmac(): void
    {
        // Stub the responses
        ApiStub::stubResponses(['access_token_grant']);

        $hmacParams = [
            'hmac'      => 'badhmac',
            'shop'      => 'example.myshopify.com',
            'code'      => '1234678',
            'timestamp' => '1337178173',
        ];

        $response = $this->call('get', '/authenticate', $hmacParams);
        $response->assertStatus(500);
    }

    public function testReturnToMethod(): void
    {
        // Stub the responses
        ApiStub::stubResponses(['access_token_grant']);

        // Set return to URL
        $this->app['session']->put('return_to', 'http://localhost/orders');

        // HMAC for regular tests
        $hmac = 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163';
        $hmacParams = [
            'hmac'      => $hmac,
            'shop'      => 'example.myshopify.com',
            'code'      => '1234678',
            'timestamp' => '1337178173',
        ];

        $response = $this->call('get', '/authenticate', $hmacParams);
        $response->assertRedirect('http://localhost/orders');
    }

    public function testOauthRedirect(): void
    {
        // Run the request
        $response = $this->call('get', '/authenticate/oauth', ['shop' => 'example.myshopify.com']);
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate'
        );
    }
}
