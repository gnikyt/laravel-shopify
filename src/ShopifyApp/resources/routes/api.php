<?php

use Illuminate\Support\Facades\Route;
use Osiset\ShopifyApp\Http\Controllers\ApiController;
use Osiset\ShopifyApp\Http\Controllers\WebhookController;
use Osiset\ShopifyApp\Util;

// Check if manual routes override is to be use
$manualRoutes = Util::getShopifyConfig('manual_routes');

if ($manualRoutes) {
    // Get a list of route names to exclude
    $manualRoutes = explode(',', $manualRoutes);
}

Route::group(['middleware' => ['api']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Exposes endpoints for the current user data, and all plans.
    |
    */

    if (Util::registerPackageRoute('api', $manualRoutes)) {
        Route::group(['prefix' => 'api', 'middleware' => ['verify.shopify']], function () {
            Route::get(
                '/',
                ApiController::class.'@index'
            );

            Route::get(
                '/me',
                ApiController::class.'@getSelf'
            );

            Route::get(
                '/plans',
                ApiController::class.'@getPlans'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook Handler
    |--------------------------------------------------------------------------
    |
    | Handles incoming webhooks.
    |
    */

    if (Util::registerPackageRoute('webhook', $manualRoutes)) {
        Route::post(
            '/webhook/{type}',
            WebhookController::class.'@handle'
        )
        ->middleware('auth.webhook')
        ->name(Util::getShopifyConfig('route_names.webhook'));
    }
});
