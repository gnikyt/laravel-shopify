<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Actions\AuthenticateShop;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;

class AuthenticateShopTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\AuthenticateShop
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(AuthenticateShop::class);
    }

    public function testShouldGoToLoginForInvalid(): void
    {
        // Build request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop' => 'some-shop.myshopify.com',
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
            Request::server()
        );
        Request::swap($newRequest);

        // Run the action
        [, $status] = call_user_func($this->action, $newRequest);

        $this->assertFalse($status);
    }

    public function testShouldGoToAuthRedirectForInvalidHmac(): void
    {
        // Build request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => 'badhmac',
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
            Request::server()
        );
        Request::swap($newRequest);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['access_token']);

        // Run the action
        [, $status] = call_user_func($this->action, $newRequest);

        $this->assertNull($status);
    }

    public function testRuns(): void
    {
        // Build request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '3d9768c9cc44b8bd66125cb82b6a59a3d835432f560d19b3f79b9fc696ef6396',
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
            Request::server()
        );
        Request::swap($newRequest);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['access_token']);

        // Run the action
        [, $status] = call_user_func($this->action, $newRequest);

        $this->assertTrue($status);
    }
}
