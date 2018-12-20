<?php

namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Setup database
        $this->setupDatabase($this->app);
        $this->withFactories(__DIR__.'/../src/ShopifyApp/resources/database/factories');
    }

    protected function getPackageProviders($app)
    {
        // ConsoleServiceProvider required to make migrations work
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class,
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
            'prefix'   => '',
        ]);
    }

    protected function setupDatabase($app)
    {
        // Path to our migrations to load
        $this->loadMigrationsFrom(realpath(__DIR__.'/../src/ShopifyApp/resources/database/migrations'));
    }
}
