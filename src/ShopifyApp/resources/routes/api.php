<?php

Route::group(['middleware' => ['api']], function () {
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Exposes endpoints for the current user data, and all plans.
    |
    */

    Route::group(['prefix' => 'api', 'middleware' => ['auth.token']], function () {
        Route::get(
            '/',
            'Osiset\ShopifyApp\Http\Controllers\ApiController@index'
        );

        Route::get(
            '/me',
            'Osiset\ShopifyApp\Http\Controllers\ApiController@getSelf'
        );

        Route::get(
            '/plans',
            'Osiset\ShopifyApp\Http\Controllers\ApiController@getPlans'
        );
    });

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
        'Osiset\ShopifyApp\Http\Controllers\WebhookController@handle'
    )
    ->middleware('auth.webhook')
    ->name('webhook');
});
