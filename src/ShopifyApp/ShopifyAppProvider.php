<?php

namespace Osiset\ShopifyApp;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;
use Osiset\ShopifyApp\Actions\ActivatePlan as ActivatePlanAction;
use Osiset\ShopifyApp\Actions\ActivateUsageCharge as ActivateUsageChargeAction;
use Osiset\ShopifyApp\Actions\AfterAuthorize as AfterAuthorizeAction;
use Osiset\ShopifyApp\Actions\AuthenticateShop as AuthenticateShopAction;
use Osiset\ShopifyApp\Actions\AuthorizeShop as AuthorizeShopAction;
use Osiset\ShopifyApp\Actions\CancelCharge as CancelChargeAction;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan as CancelCurrentPlanAction;
use Osiset\ShopifyApp\Actions\CreateScripts as CreateScriptsAction;
use Osiset\ShopifyApp\Actions\CreateWebhooks as CreateWebhooksAction;
use Osiset\ShopifyApp\Actions\DeleteWebhooks as DeleteWebhooksAction;
use Osiset\ShopifyApp\Actions\DispatchScripts as DispatchScriptsAction;
use Osiset\ShopifyApp\Actions\DispatchWebhooks as DispatchWebhooksAction;
use Osiset\ShopifyApp\Actions\GetPlanUrl as GetPlanUrlAction;
use Osiset\ShopifyApp\Console\WebhookJobMakeCommand;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use Osiset\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Http\Middleware\AuthProxy;
use Osiset\ShopifyApp\Http\Middleware\AuthShopify;
use Osiset\ShopifyApp\Http\Middleware\AuthToken;
use Osiset\ShopifyApp\Http\Middleware\AuthWebhook;
use Osiset\ShopifyApp\Http\Middleware\Billable;
use Osiset\ShopifyApp\Messaging\Jobs\ScripttagInstaller;
use Osiset\ShopifyApp\Messaging\Jobs\WebhookInstaller;
use Osiset\ShopifyApp\Services\ApiHelper;
use Osiset\ShopifyApp\Services\ChargeHelper;
use Osiset\ShopifyApp\Services\CookieHelper;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Storage\Commands\Charge as ChargeCommand;
use Osiset\ShopifyApp\Storage\Commands\Shop as ShopCommand;
use Osiset\ShopifyApp\Storage\Observers\Shop as ShopObserver;
use Osiset\ShopifyApp\Storage\Queries\Charge as ChargeQuery;
use Osiset\ShopifyApp\Storage\Queries\Plan as PlanQuery;
use Osiset\ShopifyApp\Storage\Queries\Shop as ShopQuery;

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
        $this->bootRoutes();
        $this->bootViews();
        $this->bootConfig();
        $this->bootDatabase();
        $this->bootJobs();
        $this->bootObservers();
        $this->bootMiddlewares();
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
            // Services (start)
            IApiHelper::class => [self::CBIND, function () {
                return new ApiHelper();
            }],

            // Queriers
            IShopQuery::class => [self::CSINGLETON, function () {
                $model = $this->app['config']->get('auth.providers.users.model');
                $modelInstance = new $model();

                return new ShopQuery(
                    $modelInstance
                );
            }],
            IPlanQuery::class => [self::CSINGLETON, function () {
                return new PlanQuery();
            }],
            IChargeQuery::class => [self::CSINGLETON, function () {
                return new ChargeQuery();
            }],

            // Commands
            IChargeCommand::class => [self::CSINGLETON, function ($app) {
                return new ChargeCommand(
                    $app->make(IChargeQuery::class)
                );
            }],
            IShopCommand::class => [self::CSINGLETON, function ($app) {
                return new ShopCommand(
                    $app->make(IShopQuery::class)
                );
            }],

            // Actions
            AuthorizeShopAction::class => [self::CBIND, function ($app) {
                return new AuthorizeShopAction(
                    $app->make(IShopQuery::class),
                    $app->make(IShopCommand::class),
                    $app->make(ShopSession::class)
                );
            }],
            AuthenticateShopAction::class => [self::CBIND, function ($app) {
                return new AuthenticateShopAction(
                    $app->make(ShopSession::class),
                    $app->make(IApiHelper::class),
                    $app->make(AuthorizeShopAction::class),
                    $app->make(DispatchScriptsAction::class),
                    $app->make(DispatchWebhooksAction::class),
                    $app->make(AfterAuthorizeAction::class)
                );
            }],
            GetPlanUrlAction::class => [self::CBIND, function ($app) {
                return new GetPlanUrlAction(
                    $app->make(ChargeHelper::class),
                    $app->make(IPlanQuery::class),
                    $app->make(IShopQuery::class)
                );
            }],
            CancelCurrentPlanAction::class => [self::CBIND, function ($app) {
                return new CancelCurrentPlanAction(
                    $app->make(IShopQuery::class),
                    $app->make(IChargeCommand::class),
                    $app->make(ChargeHelper::class)
                );
            }],
            DispatchWebhooksAction::class => [self::CBIND, function ($app) {
                return new DispatchWebhooksAction(
                    $app->make(IShopQuery::class),
                    WebhookInstaller::class
                );
            }],
            DispatchScriptsAction::class => [self::CBIND, function ($app) {
                return new DispatchScriptsAction(
                    $app->make(IShopQuery::class),
                    ScripttagInstaller::class
                );
            }],
            AfterAuthorizeAction::class => [self::CBIND, function ($app) {
                return new AfterAuthorizeAction(
                    $app->make(IShopQuery::class)
                );
            }],
            ActivatePlanAction::class => [self::CBIND, function ($app) {
                return new ActivatePlanAction(
                    $app->make(CancelCurrentPlanAction::class),
                    $app->make(ChargeHelper::class),
                    $app->make(IShopQuery::class),
                    $app->make(IPlanQuery::class),
                    $app->make(IChargeCommand::class),
                    $app->make(IShopCommand::class)
                );
            }],
            ActivateUsageChargeAction::class => [self::CBIND, function ($app) {
                return new ActivateUsageChargeAction(
                    $app->make(ChargeHelper::class),
                    $app->make(IChargeCommand::class),
                    $app->make(IShopQuery::class)
                );
            }],
            DeleteWebhooksAction::class => [self::CBIND, function ($app) {
                return new DeleteWebhooksAction(
                    $app->make(IShopQuery::class)
                );
            }],
            CreateWebhooksAction::class => [self::CBIND, function ($app) {
                return new CreateWebhooksAction(
                    $app->make(IShopQuery::class)
                );
            }],
            CreateScriptsAction::class => [self::CBIND, function ($app) {
                return new CreateScriptsAction(
                    $app->make(IShopQuery::class)
                );
            }],
            CancelChargeAction::class => [self::CBIND, function ($app) {
                return new CancelChargeAction(
                    $app->make(IChargeCommand::class),
                    $app->make(ChargeHelper::class)
                );
            }],

            // Observers
            ShopObserver::class => [self::CBIND, function ($app) {
                return new ShopObserver(
                    $app->make(IShopCommand::class)
                );
            }],

            // Services (end)
            ShopSession::class => [self::CBIND, function ($app) {
                return new ShopSession(
                    $app->make(AuthManager::class),
                    $app->make(IApiHelper::class),
                    $app->make(CookieHelper::class),
                    $app->make(IShopCommand::class),
                    $app->make(IShopQuery::class)
                );
            }],
            ChargeHelper::class => [self::CBIND, function ($app) {
                return new ChargeHelper(
                    $app->make(IChargeQuery::class)
                );
            }],
            CookieHelper::class => [self::CBIND, function () {
                return new CookieHelper();
            }],
        ];
        foreach ($binds as $key => $fn) {
            $this->app->{$fn[0]}($key, $fn[1]);
        }
    }

    /**
     * Boot the routes for the package.
     *
     * @return void
     */
    private function bootRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/resources/routes/shopify.php');
        $this->loadRoutesFrom(__DIR__.'/resources/routes/api.php');
    }

    /**
     * Boot the views for the package.
     *
     * @return void
     */
    private function bootViews(): void
    {
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
    }

    /**
     * Boot the config for the package.
     *
     * @return void
     */
    private function bootConfig(): void
    {
        // Config publish
        $this->publishes(
            [
                __DIR__.'/resources/config/shopify-app.php' => "{$this->app->configPath()}/shopify-app.php",
            ],
            'shopify-config'
        );
    }

    /**
     * Boot the database for the package.
     *
     * @return void
     */
    private function bootDatabase(): void
    {
        // Database migrations
        if ($this->app['config']->get('shopify-app.manual_migrations')) {
            $this->publishes(
                [
                    __DIR__.'/resources/database/migrations' => "{$this->app->databasePath()}/migrations",
                ],
                'shopify-migrations'
            );
        } else {
            $this->loadMigrationsFrom(__DIR__.'/resources/database/migrations');
        }
    }

    /**
     * Boot the jobs for the package.
     *
     * @return void
     */
    private function bootJobs(): void
    {
        // Job publish
        $this->publishes(
            [
                __DIR__.'/resources/jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
            ],
            'shopify-jobs'
        );
    }

    /**
     * Boot the observers for the package.
     *
     * @return void
     */
    private function bootObservers(): void
    {
        $model = $this->app['config']->get('auth.providers.users.model');
        $model::observe($this->app->make(ShopObserver::class));
    }

    /**
     * Boot the middlewares for the package.
     *
     * @return void
     */
    private function bootMiddlewares(): void
    {
        // Middlewares
        $this->app['router']->aliasMiddleware('auth.shopify', AuthShopify::class);
        $this->app['router']->aliasMiddleware('auth.token', AuthToken::class);
        $this->app['router']->aliasMiddleware('auth.webhook', AuthWebhook::class);
        $this->app['router']->aliasMiddleware('auth.proxy', AuthProxy::class);
        $this->app['router']->aliasMiddleware('billable', Billable::class);
    }
}
