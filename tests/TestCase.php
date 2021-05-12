<?php

namespace Osiset\ShopifyApp\Test;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Osiset\BasicShopifyAPI\Options;
use function Osiset\ShopifyApp\base64url_encode;
use Osiset\ShopifyApp\Contracts\ShopModel;
use function Osiset\ShopifyApp\createHmac;
use function Osiset\ShopifyApp\getShopifyConfig;
use Osiset\ShopifyApp\ShopifyAppProvider;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\Stubs\User as UserStub;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * User model.
     *
     * @var ShopModel
     */
    protected $model;

    /**
     * Token creation defaults.
     *
     * @var array
     */
    protected $tokenDefaults;

    public function setUp(): void
    {
        parent::setUp();

        // Setup database
        $this->setupDatabase($this->app);
        $this->withFactories(__DIR__.'/../src/ShopifyApp/resources/database/factories');

        // Assign the user model
        $this->model = $this->app['config']->get('auth.providers.users.model');

        // Token defaults
        $now = Carbon::now()->unix();
        $this->tokenDefaults = [
            'iss'  => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud'  => getShopifyConfig('api_key'),
            'sub'  => '123',
            'exp'  => $now + 60,
            'nbf'  => $now,
            'iat'  => $now,
            'jti'  => '00000000-0000-0000-0000-000000000000',
            'sid'  => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];
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

    protected function setupDatabase($app)
    {
        // Run Laravel migrations
        $this->loadLaravelMigrations();

        // Run package migration
        $this->artisan('migrate')->run();
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

    protected function buildToken(array $values = []): string
    {
        $body = base64url_encode(json_encode(array_merge($this->tokenDefaults, $values)));
        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);
        $hmac = createHmac(['data' => $payload, 'raw' => true], getShopifyConfig('api_secret'));
        $encodedHmac = base64url_encode($hmac);

        return sprintf('%s.%s', $payload, $encodedHmac);
    }

    protected function runMiddleware(string $middleware, Request $requestInstance = null, Closure $cb = null): array
    {
        $called = false;
        $requestInstance = $requestInstance ?? FacadesRequest::instance();
        $response = ($this->app->make($middleware))->handle($requestInstance, function (Request $request) use (&$called, $cb) {
            $called = true;
            if ($cb) {
                $cb($request);
            }
        });

        return [$called, $response];
    }
}
