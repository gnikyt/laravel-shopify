# Creating Webhooks

- [Creating a Webhook Job](#creating-a-webhook-job)
  - [Config Entry](#config-entry)
- [How It Works](#how-it-works)
- [Optional Custom Controller](#optional-custom-controller)

## Creating a Webhook Job

Create a new job via `php artisan make:job OrdersCreate` (App/Jobs/OrdersCreateJob).

Create a constructor in the job class where the first argument is the shop's myshopify domain and the second argument is the JSON decoded webhook data. For an example:

```php
<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class OrdersCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $data;

    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain; // example: example.myshopify.com
        $this->data = $data; // example: $this->data->line_items;
    }

    public function handle()
    {
        // Do what you wish with the data
    }
}
```

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
