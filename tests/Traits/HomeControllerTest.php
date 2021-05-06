<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Illuminate\Auth\AuthManager;
use Osiset\ShopifyApp\Test\TestCase;

class HomeControllerTest extends TestCase
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

    public function testHomeRoute(): void
    {
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        $this->call('get', '/', ['token' => $this->buildToken()])
            ->assertOk()
            ->assertSee('apiKey: "'.env('SHOPIFY_API_KEY').'"', false)
            ->assertSee("shopOrigin: \"{$shop->name}\"", false);
    }
}
