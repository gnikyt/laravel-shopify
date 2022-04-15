<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All the routes for the Shopify App setup.
|
*/

use Illuminate\Support\Facades\Route;
use Osiset\ShopifyApp\Http\Controllers\AuthController;
use Osiset\ShopifyApp\Http\Controllers\BillingController;
use Osiset\ShopifyApp\Http\Controllers\HomeController;
use Osiset\ShopifyApp\Http\Controllers\LoginController;
use Osiset\ShopifyApp\Util;

// Check if manual routes override is to be use
$manualRoutes = Util::getShopifyConfig('manual_routes');

if ($manualRoutes) {
    // Get a list of route names to exclude
    $manualRoutes = explode(',', $manualRoutes);
}

Route::group(['prefix' => Util::getShopifyConfig('prefix'), 'middleware' => ['web']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    |
    | Homepage for an authenticated store. Store is checked with the
    | auth.shopify middleware and redirected to login if not.
    |
    */

    if (Util::registerPackageRoute('home', $manualRoutes)) {
        Route::get(
            '/',
            HomeController::class.'@index'
        )
        ->middleware(['verify.shopify', 'billable'])
        ->name(Util::getShopifyConfig('route_names.home'));
    }

    /*
    |--------------------------------------------------------------------------
    | Login Route
    |--------------------------------------------------------------------------
    |
    | Login a shop.
    |
    */

    if (Util::registerPackageRoute('login', $manualRoutes)) {
        Route::get(
            '/login',
            LoginController::class . '@index'
        )
            ->name(Util::getShopifyConfig('route_names.login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate: Install & Authorize
    |--------------------------------------------------------------------------
    |
    | Install a shop and go through Shopify OAuth.
    |
    */

    if (Util::registerPackageRoute('authenticate', $manualRoutes)) {
        Route::match(
            ['GET', 'POST'],
            '/authenticate',
            AuthController::class.'@authenticate'
        )
        ->name(Util::getShopifyConfig('route_names.authenticate'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate: Token
    |--------------------------------------------------------------------------
    |
    | This route is hit when a shop comes to the app without a session token
    | yet. A token will be grabbed from Shopify AppBridge Javascript
    | and then forwarded back to the home route.
    |
    */

    if (Util::registerPackageRoute('authenticate.token', $manualRoutes)) {
        Route::get(
            '/authenticate/token',
            AuthController::class.'@token'
        )
        ->middleware(['verify.shopify'])
        ->name(Util::getShopifyConfig('route_names.authenticate.token'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Handler
    |--------------------------------------------------------------------------
    |
    | Billing handler. Sends to billing screen for Shopify.
    |
    */

    if (Util::registerPackageRoute('billing', $manualRoutes)) {
        Route::get(
            '/billing/{plan?}',
            BillingController::class.'@index'
        )
        ->middleware(['verify.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(Util::getShopifyConfig('route_names.billing'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor
    |--------------------------------------------------------------------------
    |
    | Processes the customer's response to the billing screen.
    | The shop domain is encrypted.
    |
    */

    if (Util::registerPackageRoute('billing.process', $manualRoutes)) {
        Route::get(
            '/billing/process/{plan?}',
            BillingController::class.'@process'
        )
        ->middleware(['verify.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(Util::getShopifyConfig('route_names.billing.process'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor for Usage Charges
    |--------------------------------------------------------------------------
    |
    | Creates a usage charge on a recurring charge.
    |
    */

    if (Util::registerPackageRoute('billing.usage_charge', $manualRoutes)) {
        Route::match(
            ['get', 'post'],
            '/billing/usage-charge',
            BillingController::class.'@usageCharge'
        )
        ->middleware(['verify.shopify'])
        ->name(Util::getShopifyConfig('route_names.billing.usage_charge'));
    }
});
