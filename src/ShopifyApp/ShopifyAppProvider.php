<?php

namespace OhMyBrew\ShopifyApp;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OhMyBrew\ShopifyApp\Queries\PlanQuery;
use OhMyBrew\ShopifyApp\Queries\ShopQuery;
use OhMyBrew\ShopifyApp\Services\ApiHelper;
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
use OhMyBrew\ShopifyApp\Actions\CreateWebhooksAction;
use OhMyBrew\ShopifyApp\Actions\DeleteWebhooksAction;
use OhMyBrew\ShopifyApp\Actions\DispatchScriptsAction;
use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShopAction;
use OhMyBrew\ShopifyApp\Actions\DispatchWebhooksAction;
use OhMyBrew\ShopifyApp\Actions\AfterAuthenticateAction;
use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlanAction;
use OhMyBrew\ShopifyApp\Actions\ActivateUsageChargeAction;
use OhMyBrew\ShopifyApp\Services\WebhookManager;

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

            // Services
            ApiHelper::class => [self::CBIND, function () {
                return new ApiHelper();
            }],
            ShopSession::class => [self::CBIND, function ($app) {
                return new ShopSession(
                    $app->make(ShopCommand::class)
                );
            }],
            WebhookManager::class => [self::CSINGLETON, function ($app) {
                return new WebhookManager(
                    $app->make(CreateWebhooksAction::class),
                    $app->make(DeleteWebhooksAction::class)
                );
            }],

            // Queriers
            ShopQuery::class => [self::CSINGLETON, function () {
                return new ShopQuery(Config::get('shopify-app.shop_model'));
            }],
            PlanQuery::class => [self::CSINGLETON, function () {
                return new PlanQuery();
            }],
            ChargeQuery::class => [self::CSINGLETON, function () {
                return new ChargeQuery();
            }],

            // Commands
            ChargeCommand::class => [self::CSINGLETON, function ($app) {
                return new ChargeCommand(
                    $app->make(ChargeQuery::class)
                );
            }],
            ShopCommand::class => [self::CSINGLETON, function ($app) {
                return new ShopCommand(
                    $app->make(ShopQuery::class)
                );
            }],

            // Actions
            AuthenticateShopAction::class => [self::CBIND, function ($app) {
                return new AuthenticateShopAction(
                    $app->make(ApiHelper::class),
                    $app->make(ShopQuery::class),
                    $app->make(ShopSession::class)
                );
            }],
            GetPlanUrlAction::class => [self::CBIND, function ($app) {
                return new GetPlanUrlAction(
                    $app->make(ApiHelper::class),
                    $app->make(PlanQuery::class),
                    $app->make(ShopQuery::class),
                );
            }],
            RestoreShopAction::class => [self::CBIND, function ($app) {
                return new RestoreShopAction(
                    $app->make(ShopQuery::class)
                );
            }],
            CancelCurrentPlanAction::class => [self::CBIND, function ($app) {
                return new CancelCurrentPlanAction(
                    $app->make(ShopQuery::class)
                );
            }],
            DispatchWebhooksAction::class => [self::CBIND, function ($app) {
                return new DispatchWebhooksAction(
                    $app->make(ShopQuery::class)
                );
            }],
            DispatchScriptsAction::class => [self::CBIND, function ($app) {
                return new DispatchScriptsAction(
                    $app->make(ShopQuery::class)
                );
            }],
            AfterAuthenticateAction::class => [self::CBIND, function ($app) {
                return new AfterAuthenticateAction(
                    $app->make(ShopQuery::class)
                );
            }],
            ActivatePlanAction::class => [self::CBIND, function ($app) {
                return new ActivatePlanAction(
                    $app->make(ApiHelper::class),
                    $app->make(CancelCurrentPlanAction::class),
                    $app->make(ShopQuery::class),
                    $app->make(ChargeQuery::class),
                    $app->make(PlanQuery::class),
                    $app->make(ChargeCommand::class),
                    $app->make(ShopCommand::class)
                );
            }],
            ActivateUsageChargeAction::class => [self::CBIND, function ($app) {
                return new ActivateUsageChargeAction(
                    $app->make(ApiHelper::class),
                    $app->make(ChargeCommand::class),
                    $app->make(ShopQuery::class)
                );
            }],
            DeleteWebhooksAction::class => [self::CBIND, function ($app) {
                return new DeleteWebhooksAction(
                    $app->make(ApiHelper::class),
                    $app->make(ShopQuery::class)
                );
            }],
            CreateWebhooksAction::class => [self::CBIND, function ($app) {
                return new CreateWebhooksAction(
                    $app->make(ApiHelper::class),
                    $app->make(ShopQuery::class)
                );
            }],
        ];
        foreach ($binds as $key => $fn) {
            $this->app->{$fn[0]}($key, $fn[1]);
        }
    }
}
