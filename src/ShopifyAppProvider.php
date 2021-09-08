<?php

namespace Osiset\ShopifyApp;

use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Osiset\ShopifyApp\Actions\ActivatePlan as ActivatePlanAction;
use Osiset\ShopifyApp\Actions\ActivateUsageCharge as ActivateUsageChargeAction;
use Osiset\ShopifyApp\Actions\AfterAuthorize as AfterAuthorizeAction;
use Osiset\ShopifyApp\Actions\AuthenticateShop as AuthenticateShopAction;
use Osiset\ShopifyApp\Actions\CancelCharge as CancelChargeAction;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan as CancelCurrentPlanAction;
use Osiset\ShopifyApp\Actions\CreateScripts as CreateScriptsAction;
use Osiset\ShopifyApp\Actions\CreateWebhooks as CreateWebhooksAction;
use Osiset\ShopifyApp\Actions\DeleteWebhooks as DeleteWebhooksAction;
use Osiset\ShopifyApp\Actions\DispatchScripts as DispatchScriptsAction;
use Osiset\ShopifyApp\Actions\DispatchWebhooks as DispatchWebhooksAction;
use Osiset\ShopifyApp\Actions\GetPlanUrl as GetPlanUrlAction;
use Osiset\ShopifyApp\Actions\InstallShop as InstallShopAction;
use Osiset\ShopifyApp\Console\WebhookJobMakeCommand;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use Osiset\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Directives\SessionToken;
use Osiset\ShopifyApp\Http\Middleware\AuthProxy;
use Osiset\ShopifyApp\Http\Middleware\AuthWebhook;
use Osiset\ShopifyApp\Http\Middleware\Billable;
use Osiset\ShopifyApp\Http\Middleware\VerifyShopify;
use Osiset\ShopifyApp\Macros\TokenRedirect;
use Osiset\ShopifyApp\Macros\TokenRoute;
use Osiset\ShopifyApp\Messaging\Jobs\ScripttagInstaller;
use Osiset\ShopifyApp\Messaging\Jobs\WebhookInstaller;
use Osiset\ShopifyApp\Services\ApiHelper;
use Osiset\ShopifyApp\Services\ChargeHelper;
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
        $this->bootMacros();
        $this->bootDirectives();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/resources/config/shopify-app.php', 'shopify-app');

        $this->commands([
            WebhookJobMakeCommand::class,
        ]);

        // Services (start)
        $this->app->bind(IApiHelper::class, function () {
            return new ApiHelper();
        });

        // Queriers
        $this->app->singleton(IShopQuery::class, function () {
            return new ShopQuery();
        });

        $this->app->singleton(IPlanQuery::class, function () {
            return new PlanQuery();
        });

        $this->app->singleton(IChargeQuery::class, function () {
            return new ChargeQuery();
        });

        // Commands
        $this->app->singleton(IChargeCommand::class, function ($app) {
            return new ChargeCommand($app->make(IChargeQuery::class));
        });

        $this->app->singleton(IShopCommand::class, function ($app) {
            return new ShopCommand($app->make(IShopQuery::class));
        });

        // Actions
        $this->app->bind(InstallShopAction::class, function ($app) {
            return new InstallShopAction(
                $app->make(IShopQuery::class),
                $app->make(IShopCommand::class)
            );
        });

        $this->app->bind(AuthenticateShopAction::class, function ($app) {
            return new AuthenticateShopAction(
                $app->make(IApiHelper::class),
                $app->make(InstallShopAction::class),
                $app->make(DispatchScriptsAction::class),
                $app->make(DispatchWebhooksAction::class),
                $app->make(AfterAuthorizeAction::class)
            );
        });

        $this->app->bind(GetPlanUrlAction::class, function ($app) {
            return new GetPlanUrlAction(
                $app->make(ChargeHelper::class),
                $app->make(IPlanQuery::class),
                $app->make(IShopQuery::class)
            );
        });

        $this->app->bind(CancelCurrentPlanAction::class, function ($app) {
            return new CancelCurrentPlanAction(
                $app->make(IShopQuery::class),
                $app->make(IChargeCommand::class),
                $app->make(ChargeHelper::class)
            );
        });

        $this->app->bind(DispatchWebhooksAction::class, function ($app) {
            return new DispatchWebhooksAction(
                $app->make(IShopQuery::class),
                WebhookInstaller::class
            );
        });

        $this->app->bind(DispatchScriptsAction::class, function ($app) {
            return new DispatchScriptsAction(
                $app->make(IShopQuery::class),
                ScripttagInstaller::class
            );
        });

        $this->app->bind(AfterAuthorizeAction::class, function ($app) {
            return new AfterAuthorizeAction($app->make(IShopQuery::class));
        });

        $this->app->bind(ActivatePlanAction::class, function ($app) {
            return new ActivatePlanAction(
                $app->make(CancelCurrentPlanAction::class),
                $app->make(ChargeHelper::class),
                $app->make(IShopQuery::class),
                $app->make(IPlanQuery::class),
                $app->make(IChargeCommand::class),
                $app->make(IShopCommand::class)
            );
        });

        $this->app->bind(ActivateUsageChargeAction::class, function ($app) {
            return new ActivateUsageChargeAction(
                $app->make(ChargeHelper::class),
                $app->make(IChargeCommand::class),
                $app->make(IShopQuery::class)
            );
        });

        $this->app->bind(DeleteWebhooksAction::class, function ($app) {
            return new DeleteWebhooksAction(
                $app->make(IShopQuery::class)
            );
        });

        $this->app->bind(CreateWebhooksAction::class, function ($app) {
            return new CreateWebhooksAction(
                $app->make(IShopQuery::class)
            );
        });

        $this->app->bind(CreateScriptsAction::class, function ($app) {
            return new CreateScriptsAction(
                $app->make(IShopQuery::class)
            );
        });

        $this->app->bind(CancelChargeAction::class, function ($app) {
            return new CancelChargeAction(
                $app->make(IChargeCommand::class),
                $app->make(ChargeHelper::class)
            );
        });

        // Observers
        $this->app->bind(ShopObserver::class, function ($app) {
            return new ShopObserver($app->make(IShopCommand::class));
        });

        // Services (end)
        $this->app->bind(ChargeHelper::class, function ($app) {
            return new ChargeHelper($app->make(IChargeQuery::class));
        });
    }

    /**
     * Boot the routes for the package.
     *
     * @return void
     */
    private function bootRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/resources/routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/resources/routes/shopify.php');
    }

    /**
     * Boot the views for the package.
     *
     * @return void
     */
    private function bootViews(): void
    {
        $viewResourcesPath = __DIR__.'/resources/views';

        $this->loadViewsFrom($viewResourcesPath, 'shopify-app');

        $this->publishes([
            $viewResourcesPath => resource_path('views/vendor/shopify-app'),
        ], 'shopify-views');
    }

    /**
     * Boot the config for the package.
     *
     * @return void
     */
    private function bootConfig(): void
    {
        $this->publishes([
            __DIR__.'/resources/config/shopify-app.php' => "{$this->app->configPath()}/shopify-app.php",
        ], 'shopify-config');
    }

    /**
     * Boot the database for the package.
     *
     * @return void
     */
    private function bootDatabase(): void
    {
        $databaseMigrationsPath = __DIR__.'/resources/database/migrations';

        if ($this->app['config']->get('shopify-app.manual_migrations')) {
            $this->publishes([
                $databaseMigrationsPath => "{$this->app->databasePath()}/migrations",
            ], 'shopify-migrations');
        } else {
            $this->loadMigrationsFrom($databaseMigrationsPath);
        }
    }

    /**
     * Boot the jobs for the package.
     *
     * @return void
     */
    private function bootJobs(): void
    {
        $this->publishes([
            __DIR__.'/resources/jobs/AppUninstalledJob.php' => "{$this->app->path()}/Jobs/AppUninstalledJob.php",
        ], 'shopify-jobs');
    }

    /**
     * Boot the observers for the package.
     *
     * @return void
     */
    private function bootObservers(): void
    {
        $model = Util::getShopifyConfig('user_model');
        $model::observe($this->app->make(ShopObserver::class));
    }

    /**
     * Boot the middlewares for the package.
     *
     * @return void
     */
    private function bootMiddlewares(): void
    {
        $this->app['router']->aliasMiddleware('auth.proxy', AuthProxy::class);
        $this->app['router']->aliasMiddleware('auth.webhook', AuthWebhook::class);
        $this->app['router']->aliasMiddleware('billable', Billable::class);
        $this->app['router']->aliasMiddleware('verify.shopify', VerifyShopify::class);
    }

    /**
     * Apply macros to Laravel framework.
     *
     * @return void
     */
    private function bootMacros(): void
    {
        Redirector::macro('tokenRedirect', new TokenRedirect());
        UrlGenerator::macro('tokenRoute', new TokenRoute());
    }

    /**
     * Init Blade directives.
     *
     * @return void
     */
    private function bootDirectives(): void
    {
        Blade::directive('sessionToken', new SessionToken());
    }
}
