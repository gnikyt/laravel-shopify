<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Illuminate\Http\Response;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

class AuthControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Setup API stub
        $this->setApiStub();
    }

    public function testAuthRedirectsToShopifyWhenNoCode(): void
    {
        // Run the request
        $response = $this->call('post', '/authenticate', ['shop' => 'example.myshopify.com']);

        // Check the view
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id='.Util::getShopifyConfig('api_key').'&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate'
        );
    }

    public function testAuthAcceptsShopWithCode(): void
    {
        // Stub the responses
        ApiStub::stubResponses(['access_token_grant']);

        // HMAC for regular tests
        $hmac = '6f16da24e8185e717f22a3373a1928fcaea7ea2401be40ab0d160f5bed7fe55a';
        $hmacParams = [
            'hmac' => $hmac,
            'shop' => 'example.myshopify.com',
            'code' => '1234678',
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
            'hmac' => 'badhmac',
            'shop' => 'example.myshopify.com',
            'code' => '1234678',
            'timestamp' => '1337178173',
        ];

        $response = $this->call('get', '/authenticate', $hmacParams);
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
