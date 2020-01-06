<?php

namespace OhMyBrew\ShopifyApp;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OhMyBrew\ShopifyApp\Queries\PlanQuery;
use OhMyBrew\ShopifyApp\Queries\ShopQuery;
use OhMyBrew\ShopifyApp\Actions\GetPlanUrl;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Middleware\Billable;
use OhMyBrew\ShopifyApp\Middleware\AuthProxy;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Commands\ChargeCommand;
use OhMyBrew\ShopifyApp\Interfaces\ChargeQuery;
use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use OhMyBrew\ShopifyApp\Observers\ShopObserver;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShop;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Actions\ActivatePlanForShop;
use OhMyBrew\ShopifyApp\Commands\ShopCommand;
use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;

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
        $this->loadViewsFrom(__DIR__.'/resources/views', 'shopify-app');

        // Views publish
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/shopify-app'),
        ], 'shopify-views');

        // Config publish
        $this->publishes([
            __DIR__.'/resources/config/shopify-app.php' => "{$this->app->configPath()}/shopify-app.php",
        ], 'shopify-config');

        // Database migrations
        // @codeCoverageIgnoreStart
        if (Config::get('shopify-app.manual_migrations')) {
            $this->publishes([
                __DIR__.'/resources/database/migrations' => "{$this->app->databasePath()}/migrations",
            ], 'shopify-migrations');
        } else {
            $this->loadMigrationsFrom(__DIR__.'/resources/database/migrations');
        }
        // @codeCoverageIgnoreEnd

        // Job publish
        $this->publishes([
            __DIR__.'/resources/jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
        ], 'shopify-jobs');

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
        $this->mergeConfigFrom(__DIR__.'/resources/config/shopify-app.php', 'shopify-app');

        // ShopifyApp facade
        $this->app->bind('shopifyapp', function ($app) {
            return new ShopifyApp($app);
        });

        // Queriers
        $this->app->bind('OhMyBrew\ShopifyApp\Queries\ShopQuery', function ($app) {
            return new ShopQuery(Config::get('shopify-app.shop_model'));
        });
        $this->app->bind('OhMyBrew\ShopifyApp\Queries\PlanQuery', function ($app) {
            return new PlanQuery();
        });
        $this->app->bind('OhMyBrew\ShopifyApp\Queries\ChargeQuery', function ($app) {
            return new ChargeQuery();
        });

        // Commands
        $this->app->bind('OhMyBrew\ShopifyApp\Commands\ChargeCommand', function ($app) {
            return new ChargeCommand(
                $app->make('OhMyBrew\ShopifyApp\Queries\ChargeQuery')
            );
        });
        $this->app->bind('OhMyBrew\ShopifyApp\Commands\ShopCommand', function ($app) {
            return new ShopCommand(
                $app->make('OhMyBrew\ShopifyApp\Queries\ShopQuery')
            );
        });

        // Actions
        $this->app->bind('OhMyBrew\ShopifyApp\Actions\AuthenticateShop', function ($app) {
            return new AuthenticateShop(
                $app->make('OhMyBrew\ShopifyApp\Queries\ShopQuery'),
                new AuthShopHandler(),
                new ShopSession()
            );
        });
        $this->app->bind('OhMyBrew\ShopifyApp\Actions\GetPlanUrl', function ($app) {
            return new GetPlanUrl(
                $app->make('OhMyBrew\ShopifyApp\Queries\PlanQuery')
            );
        });
        $this->app->bind('OhMyBrew\ShopifyApp\Actions\ActivatePlanForShop', function ($app) {
            return new ActivatePlanForShop(
                $app->make('OhMyBrew\ShopifyApp\Commands\ChargeCommand'),
                $app->make('OhMyBrew\ShopifyApp\Queries\ChargeQuery'),
                $app->make('OhMyBrew\ShopifyApp\Commands\ShopCommand')
            );
        });

        // Commands
        $this->commands([
            WebhookJobMakeCommand::class,
        ]);
    }
}
