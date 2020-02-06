<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;

class ShopSessionTest extends TestCase
{
    protected $shopSession;
    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testMakeLogsInShop(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Test initial state
        $this->assertTrue($this->shopSession->guest());
        $this->assertNull($this->shopSession->get());

        // Login the shop
        $this->shopSession->make($shop->getDomain());

        $this->assertFalse($this->shopSession->guest());
        $this->assertInstanceOf(IShopModel::class, $this->shopSession->get());
    }

    public function testAuthModeType(): void
    {
        // Default
        $this->assertTrue($this->shopSession->isType(AuthMode::OFFLINE()));

        // Change config
        $this->app['config']->set('shopify-app.api_grant_mode', AuthMode::PERUSER()->toNative());

        // Confirm
        $this->assertTrue($this->shopSession->isType(AuthMode::PERUSER()));
    }

    public function testApiInstance(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $this->assertInstanceOf(BasicShopifyAPI::class, $this->shopSession->api());
    }

    public function testGetToken(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Offline token
        $this->assertFalse($this->shopSession->getToken(true)->isNull());

        // Per user token
        $this->app['config']->set('shopify-app.api_grant_mode', AuthMode::PERUSER()->toNative());
        $this->assertTrue($this->shopSession->getToken(true)->isNull());
    }

    public function testSetAccessUser(): void
    {
        // Set the data from a fixture
        $this->shopSession->setAccess(
            json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'))
        );

        $this->assertTrue($this->shopSession->hasUser());
    }

    public function testSetAccessNormal(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());
        
        // Set the data from a fixture
        $data = json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'));
        $this->shopSession->setAccess($data);

        $this->assertEquals(
            $data->access_token,
            $this->shopSession->getToken(true)->toNative()
        );
    }

    public function testForget(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Ensure we are logged in
        $this->assertFalse($this->shopSession->guest());

        // Logout
        $this->shopSession->forget();
        $this->assertTrue($this->shopSession->guest());
    }

    public function testIsValidCompare(): void
    {
        // Create the shops
        $shop = factory($this->model)->create();
        $shop2 = factory($this->model)->create();

        // Login the first shop
        $this->shopSession->make($shop->getDomain());

        // Itself should be valid
        $this->assertTrue($this->shopSession->isValid($shop));

        // Compare to another shop
        $this->assertFalse($this->shopSession->isValid($shop2));
    }

    public function testIsValidNoCompare(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Itself should be valid
        $this->assertTrue($this->shopSession->isValid($shop));
    }
}
