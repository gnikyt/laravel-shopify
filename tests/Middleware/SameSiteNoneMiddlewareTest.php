<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\SameSiteNone;
use OhMyBrew\ShopifyApp\Test\TestCase;

class SameSiteNoneMiddlewareTest extends TestCase
{
    public function testCanDetectIncompatibleBrowser()
    {
        // Ported from https://github.com/Shopify/shopify_app/blob/3a241a229bf8e473f164dde075603e44fcac3373/test/shopify_app/middleware/same_site_cookie_middleware_test.rb
        $middleware = new SameSiteNone();

        $incompatibleUserAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/87.0.279142407 Mobile/15E148 Safari/605.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Linux; U; Android 7.0; en-US; SM-G935F Build/NRD90M) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/11.3.8.976 U3/0.8.0 Mobile Safari/534.30',
        ];

        collect($incompatibleUserAgents)->each(function ($userAgent) use ($middleware) {
            $this->assertFalse($middleware->isBrowserSameSiteNoneCompatible($userAgent, null), "User agent incorrectly marked: $userAgent");
        });

        $compatibleUserAgents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:72.0) Gecko/20100101 Firefox/72.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15',
        ];

        collect($compatibleUserAgents)->each(function ($userAgent) use ($middleware) {
            $this->assertTrue($middleware->isBrowserSameSiteNoneCompatible($userAgent, null), "User agent incorrectly marked: $userAgent");
        });
    }
}
