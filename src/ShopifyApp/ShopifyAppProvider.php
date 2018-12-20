<?php

namespace OhMyBrew\ShopifyApp;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;
use OhMyBrew\ShopifyApp\Middleware\AuthProxy;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use OhMyBrew\ShopifyApp\Middleware\Billable;
use OhMyBrew\ShopifyApp\Observers\ShopObserver;

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

        // Config publish
        $this->publishes([
            __DIR__.'/resources/config/shopify-app.php' => "{$this->app->configPath()}/shopify-app.php",
        ], 'config');

        // Database migrations
        if (Config::get('shopify-app.manual_migrations')) {
            $this->publishes([
                __DIR__.'/resources/database/migrations' => "{$this->app->databasePath()}/migrations",
            ], 'migrations');
        } else {
            $this->loadMigrationsFrom(__DIR__.'/resources/database/migrations');
        }

        // Job publish
        $this->publishes([
            __DIR__.'/resources/jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
        ], 'jobs');

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

        // Commands
        $this->commands([
            WebhookJobMakeCommand::class,
        ]);
    }
}
