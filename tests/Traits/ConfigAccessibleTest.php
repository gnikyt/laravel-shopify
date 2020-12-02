<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Closure;
use Illuminate\Support\Facades\Config;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

class ConfigTest
{
    use ConfigAccessible;
}

class ConfigAccessibleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('shopify-app.config_api_callback', function (string $key, $shop) {
            if ($key === 'api_secret') {
                return 'hello world';
            }

            return Config::get("shopify-app.{$key}");
        });
    }

    public function testGet(): void
    {
        $klass = new ConfigTest();
        $secret = $klass->getConfig('api_secret');
        $grantMode = $klass->getConfig('api_grant_mode');

        $this->assertEquals('hello world', $secret);
        $this->assertEquals('OFFLINE', $grantMode);
    }

    public function testSet(): void
    {
        $klass = new ConfigTest();
        $klass->setConfig('shopify-app.api_init', function () {
            return true;
        });

        $this->assertInstanceOf(Closure::class, $klass->getConfig('api_init'));
    }
}
