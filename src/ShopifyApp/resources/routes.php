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
    Route::get('/login', 'OhMyBrew\ShopifyApp\Controllers\AuthController@index')->name('login');
    Route::post('/login', 'OhMyBrew\ShopifyApp\Controllers\AuthController@authenticate')->name('authenticate');
});
