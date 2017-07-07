<?php namespace OhMyBrew\ShopifyApp\Test;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Orchestra\Database\ConsoleServiceProvider;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;
use OhMyBrew\ShopifyApp\Models\Shop;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setupDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
    }

    protected function setupDatabase($app) {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../src/ShopifyApp/resources/database/migrations'));

        $shop = new Shop;
        $shop->shopify_domain = 'example.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->save();
    }
}