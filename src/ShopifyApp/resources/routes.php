<?php

use OhMyBrew\ShopifyApp\Models\Plan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All the routes for the Shopify App setup.
|
*/

// Bind the billing plan to an actual plan
Route::bind('billingPlan', function ($value) {
    if ($value === null) {
        // Find the on-install plan
        return Plan::where('on_install', true)->first();
    }

    // Find the plan passed to the method
    return Plan::where('id', $value)->first();
});

Route::group(['middleware' => ['web']], function () {
    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    |
    | Homepage for an authenticated store. Store is checked with the auth.shop
    | middleware and redirected to login if not.
    |
    */

    Route::get(
        '/',
        'OhMyBrew\ShopifyApp\Controllers\HomeController@index'
    )
    ->middleware(['auth.shop', 'billable'])
    ->name('home');

    /*
    |--------------------------------------------------------------------------
    | Login Route
    |--------------------------------------------------------------------------
    |
    | Allows a shop to login/install.
    |
    */

    Route::get(
        '/login',
        'OhMyBrew\ShopifyApp\Controllers\AuthController@index'
    )->name('login');

    /*
    |--------------------------------------------------------------------------
    | Authenticate Method
    |--------------------------------------------------------------------------
    |
    | Authenticates a shop.
    |
    */

    Route::match(
        ['get', 'post'],
        '/authenticate',
        'OhMyBrew\ShopifyApp\Controllers\AuthController@authenticate'
    )
    ->name('authenticate');

    /*
    |--------------------------------------------------------------------------
    | Billing Handler
    |--------------------------------------------------------------------------
    |
    | Billing handler. Sends to billing screen for Shopify.
    |
    */

    Route::get(
        '/billing/{billingPlan?}',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@index'
    )
    ->where('billingPlan', '^([0-9]+|)$')
    ->name('billing');

    /*
    |--------------------------------------------------------------------------
    | Billing Processor
    |--------------------------------------------------------------------------
    |
    | Processes the customer's response to the billing screen.
    |
    */

    Route::get(
        '/billing/process/{billingPlan?}',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@process'
    )
    ->where('billingPlan', '^([0-9]+|)$')
    ->name('billing.process');

    /*
    |--------------------------------------------------------------------------
    | Billing Processor for Usage Charges
    |--------------------------------------------------------------------------
    |
    | Creates a usage charge on a recurring charge.
    |
    */

    Route::match(
        ['get', 'post'],
        '/billing/usage-charge',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@usageCharge'
    )
    ->name('billing.usage_charge');
});

Route::group(['middleware' => ['api']], function () {
    /*
    |--------------------------------------------------------------------------
    | Webhook Handler
    |--------------------------------------------------------------------------
    |
    | Handles incoming webhooks.
    |
    */

    Route::post(
        '/webhook/{type}',
        'OhMyBrew\ShopifyApp\Controllers\WebhookController@handle'
    )
    ->middleware('auth.webhook')
    ->name('webhook');
});
