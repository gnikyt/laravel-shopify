<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionObject;
use \ReflectionMethod;
use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;

class ScripttagInstallerJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        $this->shop = Shop::find(1);
        $this->scripttags = [
            [
                'src' => 'https://js-aplenty.com/bar.js',
                'event' => 'onload'
            ]
        ];
    }

    public function testJobAcceptsLoad()
    {
        $job = new ScripttagInstaller($this->shop, $this->scripttags);

        $refJob = new ReflectionObject($job);
        $refScripttags = $refJob->getProperty('scripttags');
        $refScripttags->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->scripttags, $refScripttags->getValue($job));
        $this->assertEquals($this->shop, $refShop->getValue($job));
    }

    public function testJobShouldTestScripttagExistanceMethod()
    {
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new ScripttagInstaller($this->shop, $this->scripttags);

        $method = new ReflectionMethod($job, 'scripttagExists');
        $method->setAccessible(true);

        $result = $method->invoke(
            $job,
            [
                (object) ['src' => 'https://js-aplenty.com/bar.js']
            ],
            [
                'src' => 'https://js-aplenty.com/bar.js'
            ]
        );
        $result_2 = $method->invoke(
            $job,
            [
                (object) ['src' => 'https://js-aplenty.com/bar.js']
            ],
            [
                'src' => 'https://js-aplenty.com/foo.js'
            ]
        );

        $this->assertTrue($result);
        $this->assertFalse($result_2);
    }

    public function testJobShouldNotRecreateScripttags()
    {
        // Replace with our API
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new ScripttagInstaller($this->shop, $this->scripttags);
        $created = $job->handle();

        // Scripttag JSON comes from fixture JSON which matches $this->scripttags
        // so this should be 0
        $this->assertEquals(0, sizeof($created));
    }

    public function testJobShouldCreateScripttags()
    {
        $scripttags = [
            [
                'src' => 'https://js-aplenty.com/fooy-dooy.js',
                'event' => 'onload'
            ]
        ];

        // Replace with our API
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new ScripttagInstaller($this->shop, $scripttags);
        $created = $job->handle();

        // $scripttags is new scripttags which does not exist in the JSON fixture
        // for scripttags, so it should create it
        $this->assertEquals(1, sizeof($created));
        $this->assertEquals($scripttags[0], $created[0]);
    }
}
