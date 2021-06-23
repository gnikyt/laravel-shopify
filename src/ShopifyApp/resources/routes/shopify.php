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
use Osiset\ShopifyApp\Helpers;

// Check if manual routes override is to be use
$manualRoutes = Helpers::getShopifyConfig('manual_routes');

if ($manualRoutes) {
    // Get a list of route names to exclude
    $manualRoutes = explode(',', $manualRoutes);
}

// Route which require ITP checks
Route::group(['prefix' => Helpers::getShopifyConfig('prefix'), 'middleware' => ['itp', 'web']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    |
    | Homepage for an authenticated store. Store is checked with the
    | auth.shopify middleware and redirected to login if not.
    |
    */

    if (Helpers::registerPackageRoute('home', $manualRoutes)) {
        Route::get(
            '/',
            'Osiset\ShopifyApp\Http\Controllers\HomeController@index'
        )
        ->middleware(['auth.shopify', 'billable'])
        ->name(Helpers::getShopifyConfig('route_names.home'));
    }

    /*
    |--------------------------------------------------------------------------
    | ITP
    |--------------------------------------------------------------------------
    |
    | Handles ITP and issues with it.
    |
    */

    if (Helpers::registerPackageRoute('itp', $manualRoutes)) {
        Route::get('/itp', 'Osiset\ShopifyApp\Http\Controllers\ItpController@attempt')
            ->name(Helpers::getShopifyConfig('route_names.itp'));
    }

    if (Helpers::registerPackageRoute('itp.ask', $manualRoutes)) {
        Route::get('/itp/ask', 'Osiset\ShopifyApp\Http\Controllers\ItpController@ask')
            ->name(Helpers::getShopifyConfig('route_names.itp.ask'));
    }
});

// Routes without ITP checks
Route::group(['prefix' => Helpers::getShopifyConfig('prefix'), 'middleware' => ['web']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | Authenticate Method
    |--------------------------------------------------------------------------
    |
    | Authenticates a shop.
    |
    */

    if (Helpers::registerPackageRoute('authenticate', $manualRoutes)) {
        Route::match(
            ['get', 'post'],
            '/authenticate',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@authenticate'
        )
        ->name(Helpers::getShopifyConfig('route_names.authenticate'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate OAuth
    |--------------------------------------------------------------------------
    |
    | Redirect to Shopify's OAuth screen.
    |
    */

    if (Helpers::registerPackageRoute('authenticate.oauth', $manualRoutes)) {
        Route::get(
            '/authenticate/oauth',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@oauth'
        )
        ->name(Helpers::getShopifyConfig('route_names.authenticate.oauth'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Handler
    |--------------------------------------------------------------------------
    |
    | Billing handler. Sends to billing screen for Shopify.
    |
    */

    if (Helpers::registerPackageRoute('billing', $manualRoutes)) {
        Route::get(
            '/billing/{plan?}',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@index'
        )
        ->middleware(['auth.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(Helpers::getShopifyConfig('route_names.billing'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor
    |--------------------------------------------------------------------------
    |
    | Processes the customer's response to the billing screen.
    |
    */

    if (Helpers::registerPackageRoute('billing.process', $manualRoutes)) {
        Route::get(
            '/billing/process/{plan?}',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@process'
        )
        ->middleware(['auth.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(Helpers::getShopifyConfig('route_names.billing.process'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor for Usage Charges
    |--------------------------------------------------------------------------
    |
    | Creates a usage charge on a recurring charge.
    |
    */

    if (Helpers::registerPackageRoute('billing.usage_charge', $manualRoutes)) {
        Route::match(
            ['get', 'post'],
            '/billing/usage-charge',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@usageCharge'
        )
        ->middleware(['auth.shopify'])
        ->name(Helpers::getShopifyConfig('route_names.billing.usage_charge'));
    }
});
