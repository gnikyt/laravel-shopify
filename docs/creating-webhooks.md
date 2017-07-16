# Creating Webhooks

## How It Works

There is a route defined in this pacakge which links `/webhook/{type}` to `WebhookController`. The controller will attempt to find a job for `{type}` in `App/Jobs` of your application. It converts the hyphenated `type` into PascelCase.

Example, if you create a webhook entry in `config/shopify-app.php` with an address of `https://(your-domain).com/webhook/orders-create` the controller will look for a job `App/Jobs/OrdersCreateJob`.

+ `/webhook/customers-update` => `App/Jobs/CustomersUpdateJob`
+ `/webhook/super-duper-hook` => `App/Jobs/SuperDuperHookJob`
+ etc...

If it fails to find the job, it will abort with a 500 HTTP status. If it is successfull in dispatching the job, it will return a empty body with a 201 status.

## Config Entry

Example of an entry in `config/shopify-app.php`:

```php
// ...
'webhooks' => [
    [
        'topic' => 'orders/create',
        'address' => 'https://some-app.com/webhooks/orders-create'
    ],
],
// ...
```

Next, create a new job via `php artisan make:job OrdersCreate` (App/Jobs/OrdersCreateJob). When a shop logs into your app, this webhook will automatically be installed and be able to accept data.

## Custom Controller

If you want to handle the dispatch of a job yourself, you may define a POST route in your `routes/web.php` to point to your own controller.

```php
Route::post(
    '/webhook/some-string-here',
    'App\Http\Controllers\CustomWebhookController'
)
```
