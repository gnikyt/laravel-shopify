<?php

namespace Osiset\ShopifyApp\Test\Services;

use Osiset\ShopifyApp\Services\CookieHelper;
use Osiset\ShopifyApp\Test\TestCase;

class CookieHelperTest extends TestCase
{
    protected $incompatibleUserAgents;
    protected $compatibleUserAgents;
    protected $badUserAgents;

    public function setUp(): void
    {
        parent::setUp();

        $this->incompatibleUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.78 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/87.0.279142407 Mobile/15E148 Safari/605.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Linux; U; Android 7.0; en-US; SM-G935F Build/NRD90M) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/11.3.8.976 U3/0.8.0 Mobile Safari/534.30',
            'UCWEB/2.0 (Java; U; MIDP-2.0; en-US; generic) U2/1.0.0 UCBrowser/9.5.0.449 U2/1.0.0 Mobile',
            'Mozilla/5.0 (iPod; CPU iPhone OS 12_0 like macOS) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/12.0 Mobile/14A5335d Safari/602.1.50',
        ];

        $this->compatibleUserAgents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.38 (KHTML, like Gecko) Version/66.6 Mobile/14A5297c Safari/602.1',
            'Mozilla/5.0 (Linux; U; Android 7.1.2; en-US; GT-N5110 Build/NJH47F) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.108 UCBrowser/12.14.0.1221 Mobile Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.100 Safari/537.36',
        ];

        $this->badUserAgents = [
            'XXXX',
            null,
            '15.15.15',
            'firefox 1.0',
        ];
    }
    /**
     * Originally from @jedimdan in previous iteration.
     */
    public function testSameSiteCookie()
    {
        foreach ($this->badUserAgents as $agent) {
            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $ch = $this->app->make(CookieHelper::class);

            $this->assertFalse($ch->checkSameSiteNoneCompatible());
        }

        foreach ($this->incompatibleUserAgents as $agent) {
            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $ch = $this->app->make(CookieHelper::class);

            $this->assertFalse($ch->checkSameSiteNoneCompatible());
        }

        foreach ($this->compatibleUserAgents as $agent) {
            $_SERVER['HTTP_USER_AGENT'] = $agent;
            $ch = $this->app->make(CookieHelper::class);

            $this->assertTrue($ch->checkSameSiteNoneCompatible());
        }
    }

    public function testSetCookiePolicy(): void
    {
        // Non-compatible check
        $_SERVER['HTTP_USER_AGENT'] = $this->incompatibleUserAgents[0];
        $ch = $this->app->make(CookieHelper::class);
        $ch->setCookiePolicy();

        $this->assertNotTrue($this->app['config']->get('session.secure'));
        $this->assertNotEquals('none', $this->app['config']->get('session.same_site'));

        // Compatible check
        $_SERVER['HTTP_USER_AGENT'] = $this->compatibleUserAgents[0];
        $ch = $this->app->make(CookieHelper::class);
        $ch->setCookiePolicy();

        $this->assertTrue($this->app['config']->get('session.expire_on_close'));
        $this->assertTrue($this->app['config']->get('session.secure'));
        $this->assertEquals('none', $this->app['config']->get('session.same_site'));
    }
}
