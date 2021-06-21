<?php

namespace Osiset\ShopifyApp\Test;

use Closure;
use Illuminate\Support\Facades\App;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Osiset\BasicShopifyAPI\Options;
use Osiset\ShopifyApp\ShopifyAppProvider;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\Stubs\User as UserStub;

abstract class TestCase extends OrchestraTestCase
{
    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        // Setup database
        $this->setupDatabase($this->app);
        $this->withFactories(__DIR__.'/../src/ShopifyApp/resources/database/factories');

        // Assign the user model
        $this->model = $this->app['config']->get('auth.providers.users.model');
    }

    protected function getPackageProviders($app): array
    {
        // ConsoleServiceProvider required to make migrations work
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    protected function resolveApplicationHttpKernel($app): void
    {
        // For adding custom the shop middleware
        $app->singleton(\Illuminate\Contracts\Http\Kernel::class, \Osiset\ShopifyApp\Test\Stubs\Kernel::class);
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

    protected function setupDatabase($app): void
    {
        // Run Laravel migrations
        $this->loadLaravelMigrations();

        // Run package migration
        $this->artisan('migrate')->run();
    }

    protected function swapEnvironment(string $env, Closure $fn): void
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

    protected function setApiStub(): void
    {
        $this->app['config']->set(
            'shopify-app.api_init',
            function (Options $opts): ApiStub {
                $ts = $this->app['config']->get('shopify-app.api_time_store');
                $ls = $this->app['config']->get('shopify-app.api_limit_store');
                $sd = $this->app['config']->get('shopify-app.api_deferrer');

                return new ApiStub(
                    $opts,
                    new $ts(),
                    new $ls(),
                    new $sd()
                );
            }
        );
    }
}
