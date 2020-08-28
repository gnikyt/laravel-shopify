<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Actions\AuthenticateShop;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;

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
        list($result, $status) = call_user_func($this->action, $newRequest);

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
        list($result, $status) = call_user_func($this->action, $newRequest);

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
            Request::server()
        );
        Request::swap($newRequest);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['access_token']);

        // Run the action
        list($result, $status) = call_user_func($this->action, $newRequest);

        $this->assertTrue($status);
    }
}
