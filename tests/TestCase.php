<?php

namespace OhMyBrew\ShopifyApp\Test;

use Closure;
use Illuminate\Support\Facades\App;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;
use Orchestra\Database\ConsoleServiceProvider;
use OhMyBrew\ShopifyApp\Test\Stubs\User as UserStub;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Setup database
        $this->setupDatabase($this->app);
        $this->withFactories(__DIR__.'/../src/ShopifyApp/resources/database/factories');
    }

    protected function getPackageProviders($app): array
    {
        // ConsoleServiceProvider required to make migrations work
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        // For the facade
        return [
            'ShopifyApp' => \OhMyBrew\ShopifyApp\Facades\ShopifyApp::class,
        ];
    }

    protected function resolveApplicationHttpKernel($app): void
    {
        // For adding custom the shop middleware
        $app->singleton(\Illuminate\Contracts\Http\Kernel::class, \OhMyBrew\ShopifyApp\Test\Stubs\Kernel::class);
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use memory SQLite, cleans it self up
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', UserStub::class);
    }

    protected function setupDatabase($app)
    {
        // Path to our migrations to load
        $this->loadMigrationsFrom([
            realpath(__DIR__.'/resources/database/migrations'),
            realpath(__DIR__.'/../src/ShopifyApp/resources/database/migrations'),
        ]);
    }

    protected function swapEnvironment(string $env, Closure $fn)
    {
        // Get the current environemnt
        $currentEnv = App::environment();

        // Set the environment
        App::detectEnvironment(function () use ($env) {
            return $env;
        });

        // Run the closure
        $fn();

        // Reset
        App::detectEnvironment(function () use ($currentEnv) {
            return $currentEnv;
        });
    }
}
