<?php

namespace Osiset\ShopifyApp\Test\Services;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

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

        // Login the shop
        $status = $this->shopSession->make($shop->getDomain());

        $this->assertFalse($this->shopSession->guest());
        $this->assertTrue($status);
    }

    public function testMakeLogsInShopFailure(): void
    {
        // Login the shop
        $status = $this->shopSession->make(ShopDomain::fromNative('non-existant.myshopify.com'));

        $this->assertFalse($status);
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

    public function testGetToken(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Offline token
        $this->assertFalse($this->shopSession->getToken(true)->isNull());
        $this->assertFalse($this->shopSession->getToken()->isNull());

        // Per user token
        $this->app['config']->set('shopify-app.api_grant_mode', AuthMode::PERUSER()->toNative());
        $this->assertTrue($this->shopSession->getToken(true)->isNull());
    }

    public function testSetAccessUser(): void
    {
        // Set the data from a fixture
        $this->shopSession->setAccess(
            new ResponseAccess(
                json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'), true)
            )
        );

        $this->assertTrue($this->shopSession->hasUser());
        $this->assertFalse($this->shopSession->isUserExpired());
    }

    public function testSetAccessNormal(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Set the data from a fixture
        $data = new ResponseAccess(
            json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'), true)
        );
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
        $this->assertTrue($this->shopSession->isValidCompare($shop->getDomain()));

        // Compare to another shop
        $this->assertFalse($this->shopSession->isValidCompare($shop2->getDomain()));
    }

    public function testIsValidNoCompare(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Itself should be valid
        $this->shopSession->make($shop->getDomain());
        $this->assertTrue($this->shopSession->isValid());
    }

    public function testIsValidPerUser(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // Set the data from a fixture
        $data = new ResponseAccess(
            json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'), true)
        );
        $this->shopSession->setAccess($data);

        $this->assertTrue($this->shopSession->isValid());
    }

    public function testShopifySessionToken(): void
    {
        // Create the shop and log them in
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        // GOOD check (nothing to compare)
        $this->assertTrue($this->shopSession->isSessionTokenValid('123abc'));

        // GOOD check (with compare)
        $this->shopSession->setSessionToken('123abc');
        $this->assertEquals('123abc', $this->shopSession->getSessionToken());
        $this->assertTrue($this->shopSession->isSessionTokenValid('123abc'));

        // BAD check
        $this->assertFalse($this->shopSession->isSessionTokenValid('xyz123'));
    }
}
