<?php

namespace Osiset\ShopifyApp;

use Illuminate\Support\Facades\Config;
use LogicException;
use Osiset\ShopifyApp\Test\TestCase;

class HelpersTest extends TestCase
{
    public function testHmacCreator(): void
    {
        // Set the secret to use for HMAC creations
        $secret = 'hello';

        // Raw data
        $data = 'one-two-three';
        $this->assertSame(
            hash_hmac('sha256', $data, $secret, true),
            createHmac(['data' => $data, 'raw' => true], $secret)
        );

        // Raw data encoded
        $data = 'one-two-three';
        $this->assertSame(
            base64_encode(hash_hmac('sha256', $data, $secret, true)),
            createHmac(['data' => $data, 'raw' => true, 'encode' => true], $secret)
        );

        // Query build (sorts array and builds query string)
        $data = ['one' => 1, 'two' => 2, 'three' => 3];
        $this->assertSame(
            hash_hmac('sha256', 'one=1three=3two=2', $secret, false),
            createHmac(['data' => $data, 'buildQuery' => true], $secret)
        );
    }

    public function testRegisterPackageRoutes(): void
    {
        // Routes to exclude
        $routes = explode(',', 'home,billing');

        $this->assertTrue(registerPackageRoute('authenticate', false));
        $this->assertTrue(registerPackageRoute('authenticate', []));
        $this->assertTrue(registerPackageRoute('authenticate', $routes));
        $this->assertFalse(registerPackageRoute('home', $routes));

        $this->expectExceptionObject(new LogicException('Excluded routes must be an array', 0));
        registerPackageRoute('home', \stdClass::class);
    }

    public function testRouteNames(): void
    {
        // non-dot-notation route name
        $this->assertSame(
            getShopifyConfig('route_names.home'),
            'home'
        );

        // dot-notation route name
        $this->assertSame(
            getShopifyConfig('route_names.authenticate.oauth'),
            'authenticate.oauth'
        );
    }

    public function testGetShopifyConfig(): void
    {
        Config::set('shopify-app.config_api_callback', function (string $key, $shop) {
            if ($key === 'api_secret') {
                return 'hello world';
            }

            return Config::get("shopify-app.{$key}");
        });

        $secret = getShopifyConfig('api_secret');
        $grantMode = getShopifyConfig('api_grant_mode');

        $this->assertEquals('hello world', $secret);
        $this->assertEquals('OFFLINE', $grantMode);
    }
}
