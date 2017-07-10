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
    Route::get(
        '/',
        'OhMyBrew\ShopifyApp\Controllers\HomeController@index'
    )
    ->middleware('auth.shop')
    ->name('home');

    Route::get(
        '/login',
        'OhMyBrew\ShopifyApp\Controllers\AuthController@index'
    )->name('login');

    Route::match(
        ['get', 'post'],
        '/authenticate',
        'OhMyBrew\ShopifyApp\Controllers\AuthController@authenticate'
    )
    ->name('authenticate');
});
