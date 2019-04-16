<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopSessionTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->shop = factory(Shop::class)->create();
    }

    public function testCanSetAccessPerUser()
    {
        // Get the access token JSON
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'));

        // Assert defaults
        $this->assertFalse(Config::get('session.expire_on_close'));
        $this->assertNull(Session::get(ShopSession::USER));
        $this->assertNull(Session::get(ShopSession::TOKEN));

        // Run the code
        $ss = new ShopSession($this->shop);
        $ss->setAccess($fixture);

        // Confirm changes
        $this->assertTrue(Config::get('session.expire_on_close'));
        $this->assertEquals($fixture->associated_user, $ss->getUser());
        $this->assertEquals($fixture->access_token, $ss->getToken());
        $this->assertEquals(ShopSession::GRANT_PERUSER, $ss->getType());
        $this->assertTrue($ss->isType(ShopSession::GRANT_PERUSER));
    }

    public function testCanSetAccessOffline()
    {
        // Get the access token JSON
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'));

        // Assert defaults
        $this->assertNull(Session::get(ShopSession::USER));
        $this->assertNull(Session::get(ShopSession::TOKEN));

        // Run the code
        $ss = new ShopSession($this->shop);
        $ss->setAccess($fixture);

        // Confirm changes
        $this->assertNull($ss->getUser());
        $this->assertEquals($fixture->access_token, $ss->getToken());
        $this->assertEquals(ShopSession::GRANT_OFFLINE, $ss->getType());
        $this->assertTrue($ss->isType(ShopSession::GRANT_OFFLINE));
        $this->assertEquals($ss->getToken(), $this->shop->shopify_token);
    }

    public function testCanSetDomain()
    {
        // Assert defaults
        $this->assertNull(Session::get(ShopSession::DOMAIN));

        // Run the code
        $ss = new ShopSession($this->shop);
        $ss->setDomain($this->shop->shopify_domain);

        // Confirm changes
        $this->assertTrue($ss->isType(ShopSession::GRANT_OFFLINE));
        $this->assertEquals($this->shop->shopify_domain, $ss->getDomain());
    }

    public function testCanForget()
    {
        // Run the code
        $ss = new ShopSession($this->shop);
        $ss->forget();

        // Confirm
        $this->assertNull(Session::get(ShopSession::USER));
        $this->assertNull(Session::get(ShopSession::TOKEN));
        $this->assertNull(Session::get(ShopSession::DOMAIN));
    }
}
