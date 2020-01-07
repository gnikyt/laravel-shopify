<?php

namespace OhMyBrew\ShopifyApp;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OhMyBrew\ShopifyApp\Queries\PlanQuery;
use OhMyBrew\ShopifyApp\Queries\ShopQuery;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Middleware\Billable;
use OhMyBrew\ShopifyApp\Commands\ShopCommand;
use OhMyBrew\ShopifyApp\Middleware\AuthProxy;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Commands\ChargeCommand;
use OhMyBrew\ShopifyApp\Interfaces\ChargeQuery;
use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use OhMyBrew\ShopifyApp\Observers\ShopObserver;
use OhMyBrew\ShopifyApp\Actions\GetPlanUrlAction;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Actions\RestoreShopAction;
use OhMyBrew\ShopifyApp\Actions\ActivatePlanAction;
use OhMyBrew\ShopifyApp\Actions\AfterAuthenticateAction;
use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShopAction;
use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlanAction;
use OhMyBrew\ShopifyApp\Actions\InstallWebhooksAction;

/**
 * This package's provider for Laravel.
 */
class ShopifyAppProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/resources/routes.php');

        // Views
        $this->loadViewsFrom(
            __DIR__.'/resources/views',
            'shopify-app'
        );

        // Views publish
        $this->publishes(
            [
                __DIR__.'/resources/views' => resource_path('views/vendor/shopify-app'),
            ],
            'shopify-views'
        );

        // Config publish
        $this->publishes(
            [
                __DIR__.'/resources/config/shopify-app.php' => "{$this->app->configPath()}/shopify-app.php",
            ],
            'shopify-config'
        );

        // Database migrations
        // @codeCoverageIgnoreStart
        if (Config::get('shopify-app.manual_migrations')) {
            $this->publishes(
                [
                    __DIR__.'/resources/database/migrations' => "{$this->app->databasePath()}/migrations",
                ],
                'shopify-migrations'
            );
        } else {
            $this->loadMigrationsFrom(__DIR__.'/resources/database/migrations');
        }
        // @codeCoverageIgnoreEnd

        // Job publish
        $this->publishes(
            [
                __DIR__.'/resources/jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
            ],
            'shopify-jobs'
        );

        // Shop observer
        $shopModel = Config::get('shopify-app.shop_model');
        $shopModel::observe(ShopObserver::class);

        // Middlewares
        $this->app['router']->aliasMiddleware('auth.shop', AuthShop::class);
        $this->app['router']->aliasMiddleware('auth.webhook', AuthWebhook::class);
        $this->app['router']->aliasMiddleware('auth.proxy', AuthProxy::class);
        $this->app['router']->aliasMiddleware('billable', Billable::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge options with published config
        $this->mergeConfigFrom(
            __DIR__.'/resources/config/shopify-app.php',
            'shopify-app'
        );

        // Commands
        $this->commands([
            WebhookJobMakeCommand::class,
        ]);

        // Binds
        $binds = [
            // Facades
            'shopifyapp' => function ($app) {
                return new ShopifyApp($app);
            },

            // Queriers
            'Queries\ShopQuery' => function () {
                return new ShopQuery(Config::get('shopify-app.shop_model'));
            },
            'Queries\PlanQuery' => function () {
                return new PlanQuery();
            },
            'Queries\ChargeQuery' => function () {
                return new ChargeQuery();
            },

            // Commands
            'Commands\ChargeCommand' => function ($app) {
                return new ChargeCommand(
                    $app->make($this->createClassPath('Queries\ChargeQuery'))
                );
            },
            'Commands\ShopCommand' => function ($app) {
                return new ShopCommand(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            },

            // Actions
            'Actions\AuthenticateShopAction' => function ($app) {
                return new AuthenticateShopAction(
                    $app->make($this->createClassPath('Queries\ShopQuery')),
                    new AuthShopHandler(),
                    new ShopSession()
                );
            },
            'Actions\GetPlanUrlAction' => function ($app) {
                return new GetPlanUrlAction(
                    $app->make($this->createClassPath('Queries\PlanQuery')),
                    $app->make($this->createClassPath('Queryies\ShopQuery')),
                );
            },
            'Actions\RestoreShopAction' => function ($app) {
                return new RestoreShopAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            },
            'Actions\CancelCurrentPlanAction' => function ($app) {
                return new CancelCurrentPlanAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            },
            'Actions\InstallWebhooksAction' => function ($app) {
                return new InstallWebhooksAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            },
            'Actions\AfterAuthenticateAction' => function ($app) {
                return new AfterAuthenticateAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            },
            'Actions\ActivatePlanAction' => function ($app) {
                return new ActivatePlanAction(
                    $app->make($this->createClassPath('Actions\CancelCurrentPlanAction')),
                    $app->make($this->createClassPath('Queryies\ShopQuery')),
                    $app->make($this->createClassPath('Queries\ChargeQuery')),
                    $app->make($this->createClassPath('Queries\PlanQuery')),
                    $app->make($this->createClassPath('Commands\ChargeCommand')),
                    $app->make($this->createClassPath('Commands\ShopCommand'))
                );
            },
        ];
        foreach ($binds as $key => $fn) {
            $this->createBind($key, $fn);
        }
    }

    /**
     * Simple helper for creating binds.
     *
     * @param string  $obj  The relative path to the class or the bind key.
     * @param Closure $fn   The callback passed to the bind.
     *
     * @return void
     */
    private function createBind(string $obj, Closure $fn): void
    {
        if (strstr($obj, '\\')) {
            $obj = $this->createClassPath($obj);
        }

        $this->app->bind($obj, $fn);
    }

    /**
     * Simple helper for writing the class paths.
     *
     * @param string $partialpath The part of the path.
     *
     * @return string
     */
    private function createClassPath(string $partialPath): string
    {
        return "OhMyBrew\ShopifyApp\{$partialPath}";
    }
}
