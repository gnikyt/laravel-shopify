<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All the routes for the Shopify App setup.
|
*/

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use function Osiset\ShopifyApp\registerPackageRoute;

// Check if manual routes override is to be use
$manualRoutes = Config::get('shopify-app.manual_routes');
if ($manualRoutes) {
    // Get a list of route names to exclude
    $manualRoutes = explode(',', $manualRoutes);
}

Route::group(['prefix' => config('shopify-app.prefix'), 'middleware' => ['web']], function () use ($manualRoutes) {
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
        ->middleware(['auth.shopify', 'billable'])
        ->name('home');
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate Method
    |--------------------------------------------------------------------------
    |
    | Authenticates a shop.
    |
    */

    if (registerPackageRoute('authenticate', $manualRoutes)) {
        Route::match(
            ['get', 'post'],
            '/authenticate',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@authenticate'
        )
        ->name('authenticate');
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate OAuth
    |--------------------------------------------------------------------------
    |
    | Redirect to Shopify's OAuth screen.
    |
    */

    if (registerPackageRoute('authenticate.oauth', $manualRoutes)) {
        Route::get(
            '/authenticate/oauth',
            'Osiset\ShopifyApp\Http\Controllers\AuthController@oauth'
        )
        ->name('authenticate.oauth');
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
        ->name('billing');
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
        ->name('billing.process');
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
        ->name('billing.usage_charge');
    }
});

Route::group(['middleware' => ['api']], function () use ($manualRoutes) {
    /*
    |--------------------------------------------------------------------------
    | Webhook Handler
    |--------------------------------------------------------------------------
    |
    | Handles incoming webhooks.
    |
    */

    if (registerPackageRoute('webhook', $manualRoutes)) {
        Route::post(
            '/webhook/{type}',
            'Osiset\ShopifyApp\Http\Controllers\WebhookController@handle'
        )
        ->middleware('auth.webhook')
        ->name('webhook');
    }
});
