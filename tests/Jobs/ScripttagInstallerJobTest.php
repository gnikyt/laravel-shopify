<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;
use ReflectionObject;

class ScripttagInstallerJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        // Script tags to use
        $this->scripttags = [
            [
                'src'   => 'https://js-aplenty.com/bar.js',
                'event' => 'onload',
            ],
        ];

        // Replace with our API
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testJobAcceptsLoad()
    {
        $shop = factory(Shop::class)->create();
        $job = new ScripttagInstaller($shop, $this->scripttags);

        $refJob = new ReflectionObject($job);
        $refScripttags = $refJob->getProperty('scripttags');
        $refScripttags->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->scripttags, $refScripttags->getValue($job));
        $this->assertEquals($shop, $refShop->getValue($job));
    }

    public function testJobShouldTestScripttagExistanceMethod()
    {
        $shop = factory(Shop::class)->create();
        $job = new ScripttagInstaller($shop, $this->scripttags);

        $method = new ReflectionMethod($job, 'scripttagExists');
        $method->setAccessible(true);

        $result = $method->invoke(
            $job,
            [
                // Existing scripttags
                (object) ['src' => 'https://js-aplenty.com/bar.js'],
            ],
            [
                // Defined scripttag in config
                'src' => 'https://js-aplenty.com/bar.js',
            ]
        );
        $result_2 = $method->invoke(
            $job,
            [
                // Existing scripttags
                (object) ['src' => 'https://js-aplenty.com/bar.js'],
            ],
            [
                // Defined scripttag in config
                'src' => 'https://js-aplenty.com/foo.js',
            ]
        );

        $this->assertTrue($result);
        $this->assertFalse($result_2);
    }

    public function testJobShouldNotRecreateScripttags()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_script_tags',
        ]);

        $shop = factory(Shop::class)->create();
        $job = new ScripttagInstaller($shop, $this->scripttags);
        $created = $job->handle();

        // Scripttag JSON comes from fixture JSON which matches $this->scripttags
        // so this should be 0
        $this->assertEquals(0, count($created));
    }

    public function testJobShouldCreateScripttags()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_script_tags',
            'get_script_tags',
        ]);

        $scripttags = [
            [
                'src'   => 'https://js-aplenty.com/fooy-dooy.js',
                'event' => 'onload',
            ],
        ];

        $shop = factory(Shop::class)->create();
        $job = new ScripttagInstaller($shop, $scripttags);
        $created = $job->handle();

        // $scripttags is new scripttags which does not exist in the JSON fixture
        // for scripttags, so it should create it
        $this->assertEquals(1, count($created));
        $this->assertEquals($scripttags[0], $created[0]);
    }
}
