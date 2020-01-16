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
use OhMyBrew\ShopifyApp\Actions\ActivateUsageChargeAction;
use OhMyBrew\ShopifyApp\Actions\AfterAuthenticateAction;
use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShopAction;
use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlanAction;
use OhMyBrew\ShopifyApp\Actions\DispatchScriptsAction;
use OhMyBrew\ShopifyApp\Actions\DispatchWebhooksAction;
use OhMyBrew\ShopifyApp\Services\ApiHelper;

/**
 * This package's provider for Laravel.
 */
class ShopifyAppProvider extends ServiceProvider
{
    /**
     * Bind type: new instances.
     *
     * @var string
     */
    const CBIND = 'bind';

    /**
     * Bind type: singleton.
     */
    const CSINGLETON = 'singleton';

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
            'Queries\ShopQuery' => [self::CSINGLETON, function () {
                return new ShopQuery(Config::get('shopify-app.shop_model'));
            }],
            'Queries\PlanQuery' => [self::CSINGLETON, function () {
                return new PlanQuery();
            }],
            'Queries\ChargeQuery' => [self::CSINGLETON, function () {
                return new ChargeQuery();
            }],

            // Commands
            'Commands\ChargeCommand' => [self::CSINGLETON, function ($app) {
                return new ChargeCommand(
                    $app->make($this->createClassPath('Queries\ChargeQuery'))
                );
            }],
            'Commands\ShopCommand' => [self::CSINGLETON, function ($app) {
                return new ShopCommand(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],

            // Actions
            'Actions\AuthenticateShopAction' => [self::CBIND, function ($app) {
                return new AuthenticateShopAction(
                    new ApiHelper(),
                    $app->make($this->createClassPath('Queries\ShopQuery')),
                    new AuthShopHandler(),
                    new ShopSession()
                );
            }],
            'Actions\GetPlanUrlAction' => [self::CBIND, function ($app) {
                return new GetPlanUrlAction(
                    new ApiHelper(),
                    $app->make($this->createClassPath('Queries\PlanQuery')),
                    $app->make($this->createClassPath('Queryies\ShopQuery')),
                );
            }],
            'Actions\RestoreShopAction' => [self::CBIND, function ($app) {
                return new RestoreShopAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
            'Actions\CancelCurrentPlanAction' => [self::CBIND, function ($app) {
                return new CancelCurrentPlanAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
            'Actions\DispatchWebhooksAction' => [self::CBIND, function ($app) {
                return new DispatchWebhooksAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
            'Actions\DispatchScriptsAction' => [self::CBIND, function ($app) {
                return new DispatchScriptsAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
            'Actions\AfterAuthenticateAction' => [self::CBIND, function ($app) {
                return new AfterAuthenticateAction(
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
            'Actions\ActivatePlanAction' => [self::CBIND, function ($app) {
                return new ActivatePlanAction(
                    new ApiHelper(),
                    $app->make($this->createClassPath('Actions\CancelCurrentPlanAction')),
                    $app->make($this->createClassPath('Queries\ShopQuery')),
                    $app->make($this->createClassPath('Queries\ChargeQuery')),
                    $app->make($this->createClassPath('Queries\PlanQuery')),
                    $app->make($this->createClassPath('Commands\ChargeCommand')),
                    $app->make($this->createClassPath('Commands\ShopCommand'))
                );
            }],
            'Actions\ActivateUsageChargeAction' => [self::CBIND, function ($app) {
                return new ActivateUsageChargeAction(
                    new ApiHelper(),
                    $app->make($this->createClassPath('Commands\ChargeCommand')),
                    $app->make($this->createClassPath('Queries\ShopQuery'))
                );
            }],
        ];
        foreach ($binds as $key => $fn) {
            $this->createBind($key, $fn[0], $fn[1]);
        }
    }

    /**
     * Simple helper for creating binds.
     *
     * @param string  $obj  The relative path to the class or the bind key.
     * @param string  $type The type of bind (true means singleton, false is bind).
     * @param Closure $fn   The callback passed to the bind.
     *
     * @return void
     */
    private function createBind(string $obj, string $type, Closure $fn): void
    {
        if (strstr($obj, '\\')) {
            $obj = $this->createClassPath($obj);
        }

        $this->app->{$type}($obj, $fn);
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
