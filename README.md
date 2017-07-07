# Laravel Shopify App

*Work in progress/ for fun*.

A Laravel package for aiding in Shopify App integration, it will follow suit to `shopify_app` for Rails.

## Installation

### Provider

Open `config/app.php` find `providers` array. Add a new line with:

`\OhMyBrew\ShopifyApp\ShopifyAppProvider::class,`

## Facade

Open `config/app.php` find `aliases` array. Add a new line with:

`'ShopifyApp' => \OhMyBrew\ShopifyApp\ShopifyAppFacade::class,`

### Middleware

Open `app/Http/Kernel.php` find `routeMiddleware` array. Add a new line with:

`'auth.shop' => \OhMyBrew\ShopifyApp\Middleware\AuthShop::class,`

## Notes

I have not touched PHP in years, I've been primarily a full-time Ruby developer. Please forgive any quirks :)

## LICENSE

This project is released under the MIT license.