# App Proxies

- [Creating an App Proxy Controller](#creating-an-app-proxy-contreoller)
- [Route Entry](#route-entry)
- [Notes](#notes)

## Creating an App Proxy Controller

`php artisan make:controller appProxy`

This will create a file `AppProxy.php` in `app/Http/Controllers`.

## Route Entry

Open `app/routes/web.php` and create a new GET entry to point to the newly created controller.

```php
    Route::get(
        '/proxy',
        'AppProxyController@index'
    )
    ->middleware('auth.proxy')
    ->name('proxy');
```

This will point `/proxy` to `AppProxyController` and it's method `index`. The key here is the use of the `auth.proxy` middleware which will take care of validating the proxy signature before sending the request to the controller.

You're now free to create an app proxy entry in your app's configuration in the partner dashboard, point the URL to your new proxy route, example: `https://your-domain.com/proxy`.

## Notes

Be sure to return a 200 response on your controller method. If you wish to integrate nicely with the shop's theming be sure to also respond with content type being `application/liquid`.

For more information, see [Shopify's docs](https://help.shopify.com/api/tutorials/application-proxies).