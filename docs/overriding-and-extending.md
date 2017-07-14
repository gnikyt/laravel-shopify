# Overriding / Extending

## Views

Laravel will look for views in `resources/views/vendor/shopify-app`.

To override the homepage view you would create `resources/views/vendor/shopify-app/home/index.blade.php`.

## Routes

Because our provider is loaded before the app providers, you're free to use your `routes/web.php` to override routes.

To override the homepage route to point to your controller you can add the following to `routes/web.php`:

```php
    Route::get(
        '/',
        'App\Http\Controllers\HomeController@index'
    )
    ->middleware('auth.shop')
    ->name('home');
```

`/` will now point to your `HomeController` where you can return your own code/view or optionally extend `\OhMyBrew\ShopifyApp\Controllers\HomeController`.

## Models

You can create `Shop.php` in `App` folder and extend the package's model.

```php
<?php

use OhMyBrew\ShopifyApp\Models\Shop as BaseShop;

class Shop extends BaseShop
{
    // Your extensions or changes
}
```
