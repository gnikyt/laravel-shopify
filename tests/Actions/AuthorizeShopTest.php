<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Actions\AuthorizeShop;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;

class AuthorizeShopTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\AuthorizeShop
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(AuthorizeShop::class);
    }

    public function testNoShopShouldBeMade(): void
    {
        $result = call_user_func(
            $this->action,
            ShopDomain::fromNative('non-existant.myshopify.com'),
            null
        );

        $this->assertStringContainsString(
            '/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate',
            $result->url
        );
        $this->assertFalse($result->completed);
    }

    public function testWithoutCode(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        $result = call_user_func(
            $this->action,
            $shop->getDomain(),
            null
        );

        $this->assertStringContainsString(
            '/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate',
            $result->url
        );
        $this->assertFalse($result->completed);
    }

    public function testWithCode(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Get the current access token
        $currentToken = $shop->getToken();

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['access_token']);

        $result = call_user_func(
            $this->action,
            $shop->getDomain(),
            '12345678'
        );

        // Refresh to see changes
        $shop->refresh();

        $this->assertTrue($result->completed);
        $this->assertNotEquals($currentToken->toNative(), $shop->getToken()->toNative());
    }
}
