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
use function Osiset\ShopifyApp\getShopifyConfig;
use function Osiset\ShopifyApp\registerPackageRoute;

// Check if manual routes override is to be use
$manualRoutes = getShopifyConfig('manual_routes');

if ($manualRoutes) {
    // Get a list of route names to exclude
    $manualRoutes = explode(',', $manualRoutes);
}

Route::group(['prefix' => getShopifyConfig('prefix'), 'middleware' => ['web']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    |
    | Homepage for an authenticated store. Store is checked with the
    | auth.shopify middleware and redirected to login if not.
    |
    */

    if (registerPackageRoute('home', $manualRoutes)) {
        Route::get(
            '/',
            'Osiset\ShopifyApp\Http\Controllers\HomeController@index'
        )
        ->middleware(['verify.shopify', 'billable'])
        ->name(getShopifyConfig('route_names.home'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate: Install & Authorize
    |--------------------------------------------------------------------------
    |
    | Install a shop and go through Shopify OAuth.
    |
    */

    if (registerPackageRoute('authenticate', $manualRoutes)) {
        Route::get(
            '/authenticate',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@authenticate'
        )
        ->name(getShopifyConfig('route_names.authenticate'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate: Token
    |--------------------------------------------------------------------------
    |
    | This route is hit when a shop comes to the app without a session token
    | yet. A token will be grabbed from Shopify's AppBridge Javascript
    | and then forwarded back to the home route.
    |
    */

    if (registerPackageRoute('authenticate.token', $manualRoutes)) {
        Route::get(
            '/authenticate/token',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@token'
        )
        ->middleware(['verify.shopify'])
        ->name(getShopifyConfig('route_names.authenticate.token'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Handler
    |--------------------------------------------------------------------------
    |
    | Billing handler. Sends to billing screen for Shopify.
    |
    */

    if (registerPackageRoute('billing', $manualRoutes)) {
        Route::get(
            '/billing/{plan?}',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@index'
        )
        ->middleware(['auth.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(getShopifyConfig('route_names.billing'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor
    |--------------------------------------------------------------------------
    |
    | Processes the customer's response to the billing screen.
    |
    */

    if (registerPackageRoute('billing.process', $manualRoutes)) {
        Route::get(
            '/billing/process/{plan?}',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@process'
        )
        ->middleware(['auth.shopify'])
        ->where('plan', '^([0-9]+|)$')
        ->name(getShopifyConfig('route_names.billing.process'));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Processor for Usage Charges
    |--------------------------------------------------------------------------
    |
    | Creates a usage charge on a recurring charge.
    |
    */

    if (registerPackageRoute('billing.usage_charge', $manualRoutes)) {
        Route::match(
            ['get', 'post'],
            '/billing/usage-charge',
            'Osiset\ShopifyApp\Http\Controllers\BillingController@usageCharge'
        )
        ->middleware(['auth.shopify'])
        ->name(getShopifyConfig('route_names.billing.usage_charge'));
    }
});
