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

        // Setup database
        $this->setupDatabase($this->app);
        $this->seedDatabase();
    }

    protected function getPackageProviders($app)
    {
        // ConsoleServiceProvider required to make migrations work
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        // For the facade
        return [
            'ShopifyApp' => \OhMyBrew\ShopifyApp\Facades\ShopifyApp::class,
        ];
    }

    protected function resolveApplicationHttpKernel($app)
    {
        // For adding custom the shop middleware
        $app->singleton('Illuminate\Contracts\Http\Kernel', 'OhMyBrew\ShopifyApp\Test\Stubs\Kernel');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use memory SQLite, cleans it self up
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
    }

    protected function setupDatabase($app)
    {
        // Path to our migrations to load
        $this->loadMigrationsFrom(realpath(__DIR__.'/../src/ShopifyApp/resources/database/migrations'));
    }

    protected function seedDatabase()
    {
        // Base shop we use in most tests
        $shop = new Shop;
        $shop->shopify_domain = 'example.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->charge_id = 678298290;
        $shop->save();

        $shop = new Shop;
        $shop->shopify_domain = 'grandfathered.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->grandfathered = true;
        $shop->save();
    }
}