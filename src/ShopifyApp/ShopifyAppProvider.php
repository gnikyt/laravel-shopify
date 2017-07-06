<?php namespace OhMyBrew\ShopifyApp;

use Illuminate\Support\ServiceProvider;

class ShopifyAppProvider extends ServiceProvider
{
    /**
    * Bootstrap the application services.
    *
    * @return void
    */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/resources/routes.php');
    }

    /**
    * Register the application services.
    *
    * @return void
    */
    public function register()
    {
        //
    }
}
