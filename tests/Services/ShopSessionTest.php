<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopSessionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->shop = factory(Shop::class)->create();
    }

    public function testCanSetAccessPerUser()
    {
        // Change config
        Config::set('shopify-app.api_grant_mode', ShopSession::GRANT_PERUSER);

        // Get the access token JSON
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'));

        // Assert defaults
        $this->assertContains(Config::get('session.expire_on_close'), [null, false]);
        $this->assertNull(Session::get(ShopSession::USER));
        $this->assertNull(Session::get(ShopSession::TOKEN));

        // Run the code
        $ss = new ShopSession($this->shop);
        $ss->setAccess($fixture);

        // Confirm changes
        $this->assertTrue(Config::get('session.expire_on_close'));
        $this->assertEquals($fixture->associated_user, $ss->getUser());
        $this->assertNotNull($ss->hasUser());
        $this->assertEquals($fixture->access_token, $ss->getToken());
        $this->assertEquals(ShopSession::GRANT_PERUSER, $ss->getType());
        $this->assertTrue($ss->isType(ShopSession::GRANT_PERUSER));
    }

    public function testCanSetAccessOffline()
    {
        // Ensure config
        Config::set('shopify-app.api_grant_mode', ShopSession::GRANT_OFFLINE);

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

    public function testCanStrictlyGetToken()
    {
        // Change config
        Config::set('shopify-app.api_grant_mode', ShopSession::GRANT_PERUSER);

        // Create an isolated shop
        $shop = factory(Shop::class)->create(['shopify_token' => 'abc']);

        // Get the access token JSON
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/access_token_grant.json'));

        // Run the code
        $ss = new ShopSession($shop);
        $ss->setAccess($fixture);

        // Confirm we always get per-user
        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $ss->getToken(true));
        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $ss->getToken());
    }

    public function testCanSetDomain()
    {
        // Assert defaults
        $this->assertNull(Session::get(ShopSession::DOMAIN));

        // Start the session
        $ss = new ShopSession($this->shop);

        // Confirm its not valid
        $this->assertFalse($ss->isValid());

        // Set the domain
        $ss->setDomain($this->shop->shopify_domain);

        // Confirm changes
        $this->assertTrue($ss->isType(ShopSession::GRANT_OFFLINE));
        $this->assertEquals($this->shop->shopify_domain, $ss->getDomain());
        $this->assertTrue($ss->isValid());
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

    public function testSameSiteCookie()
    {
        $incompatibleUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.78 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/87.0.279142407 Mobile/15E148 Safari/605.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Linux; U; Android 7.0; en-US; SM-G935F Build/NRD90M) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/11.3.8.976 U3/0.8.0 Mobile Safari/534.30',
            'UCWEB/2.0 (Java; U; MIDP-2.0; en-US; generic) U2/1.0.0 UCBrowser/9.5.0.449 U2/1.0.0 Mobile',
            'Mozilla/5.0 (iPod; CPU iPhone OS 12_0 like macOS) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/12.0 Mobile/14A5335d Safari/602.1.50',
        ];

        $compatibleUserAgents = [
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.78 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:72.0) Gecko/20100101 Firefox/72.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15',
            'Mozilla/5.0 (Linux; U; Android 9; zh-CN; Nokia X7 Build/PPR1.180610.011) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.15.0.1020 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.38 (KHTML, like Gecko) Version/66.6 Mobile/14A5297c Safari/602.1',
            'Mozilla/5.0 (Linux; U; Android 7.1.2; en-US; GT-N5110 Build/NJH47F) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.14.0.1221 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; U; Android 9; zh-CN; Nokia X7 Build/PPR1.180610.011) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.14.0.1020 Mobile Safari/537.36',
            'UCWEB/2.0 (Java; U; MIDP-2.0; en-US; generic) U2/1.0.0 UCBrowser/12.15.0.449 U2/2.0.0 Mobile',
            'Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0',
            'Mozilla/5.0 zgrab/0.x',
            'AWS Security Scanner',
            'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
        ];

        $badUserAgents = [
            'XXXX',
            null,
            '15.15.15',
            'firefox 1.0',
        ];

        // Set a shop
        $shop = factory(Shop::class)->create();

        foreach ($badUserAgents as $agent) {
            // reset sessions before each test
            config([
                'session.secure'    => false,
                'session.same_site' => null,
            ]);

            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $response = $this->get('/');

            $this->assertEquals('none', $response->baseResponse->headers->getCookies()[0]->getSameSite());
            $this->assertTrue($response->baseResponse->headers->getCookies()[0]->isSecure());
        }

        foreach ($incompatibleUserAgents as $agent) {
            // reset sessions before each test
            config([
                'session.secure'    => false,
                'session.same_site' => null,
            ]);

            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $response = $this->get('/');

            $this->assertNull($response->baseResponse->headers->getCookies()[0]->getSameSite());
            $this->assertFalse($response->baseResponse->headers->getCookies()[0]->isSecure());
        }

        foreach ($compatibleUserAgents as $agent) {
            // reset sessions before each test
            config([
                'session.secure'    => false,
                'session.same_site' => null,
            ]);

            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $response = $this->get('/');

            $this->assertEquals('none', $response->baseResponse->headers->getCookies()[0]->getSameSite());
            $this->assertTrue($response->baseResponse->headers->getCookies()[0]->isSecure());
        }
    }
}
