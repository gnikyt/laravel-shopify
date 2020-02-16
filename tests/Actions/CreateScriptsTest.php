<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Actions\CreateScripts;
use OhMyBrew\ShopifyApp\Test\Stubs\Api as ApiStub;

class CreateScriptsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CreateScripts::class);
    }

    public function testShouldNotCreateIfExists(): void
    {
        // Create the config
        $scripts = [
            [
                'src' => 'https://js-aplenty.com/foo.js',
            ]
        ];
        $this->app['config']->set('shopify-app.scripttags', $scripts);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_script_tags',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $scripts
        );

        $this->assertEquals(0, count($result));
    }

    public function testShouldCreate(): void
    {
        // Create the config
        $scripts = [
            [
                'src' => 'https://js-aplenty.com/foo-bar.js',
            ]
        ];
        $this->app['config']->set('shopify-app.scripttags', $scripts);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_script_tags',
            'post_script_tags'
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $scripts
        );

        $this->assertEquals(1, count($result));
    }
}
