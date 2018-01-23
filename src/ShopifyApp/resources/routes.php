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
    | Billing Method
    |--------------------------------------------------------------------------
    |
    | Billing handler.
    |
    */
    
    Route::get(
        '/billing',
        'OhMyBrew\ShopifyApp\Controllers\BillingController@index'
    )
    ->name('billing');

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
