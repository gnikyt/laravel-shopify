# Creating Webhooks

- [Creating a Webhook Job](#creating-a-webhook-job)
  - [Config Entry](#config-entry)
- [How It Works](#how-it-works)
- [Optional Custom Controller](#optional-custom-controller)

## Creating a Webhook Job

Create a new job via `php artisan shopify-app:make:webhook [name] [topic]`.

The first argument is the class name for the job, the second is the Shopify topic/event for the webhook.

Example: `php artisan shopify-app:make:webhook OrdersCreateJob orders/create` will create a webhook job file in `App/Jobs/OrdersCreateJob.php` where you can esily modify the `handle` method to do what you need with the webhook data.

### Config Entry

Example of an entry in `config/shopify-app.php`:

```php
// ...
'webhooks' => [
    [
        'topic' => 'orders/create',
        'address' => 'https://some-app.com/webhook/orders-create'
    ],
],
// ...
```

When a shop logs into your app, this webhook entry will automatically be installed and `App/Jobs/OrdersCreateJob` will be ready to accept data.

## How It Works

There is a route defined in this pacakge which links `/webhook/{type}` to `WebhookController`. The controller will attempt to find a job for `{type}` in `App/Jobs` of your application. It converts the hyphenated `type` into PascelCase.

Example, if you create a webhook entry in `config/shopify-app.php` with an address of `https://(your-domain).com/webhook/orders-create` the controller will look for a job `App/Jobs/OrdersCreateJob`.

+ `/webhook/customers-update` => `App/Jobs/CustomersUpdateJob`
+ `/webhook/super-duper-hook` => `App/Jobs/SuperDuperHookJob`
+ etc...

If it fails to find the job, it will abort with a 500 HTTP status. If it is successfull in dispatching the job, it will return a empty body with a 201 HTTP status.

## Optional Custom Controller

If you want to handle the dispatch of a job yourself, you may define a POST route in your `routes/web.php` to point to your own controller.

```php
Route::post(
    '/webhook/some-string-here',
    'App\Http\Controllers\CustomWebhookController'
)
->middleware('auth.middleware')
```
