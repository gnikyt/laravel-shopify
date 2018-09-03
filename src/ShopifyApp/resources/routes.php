<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All the routes for the Shopify App setup.
|
*/

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
        '/billing/{planId?}',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@index'
    )
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
        '/billing/process/{planId?}',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@process'
    )
    ->name('billing.process');
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
