<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | (Not yet complete) A verbose logged output of processes
    |
    */
    'debug' => (bool) env('SHOPIFY_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Manual migrations
    |--------------------------------------------------------------------------
    |
    | This option option allows you to use:
    | `php artisan vendor:publish --tag=migrations` to push migrations
    | to your app's folder so you're free to modify before migrating.
    |
    */
    'manual_migrations' => (bool) env('SHOPIFY_MANUAL_MIGRATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | This option allows you to set a namespace.
    | Useful for multiple apps using the same database instance.
    | Meaning, one shop can be part of many apps on the same database.
    |
    */

    'namespace' => env('SHOPIFY_APP_NAMESPACE', null),

    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | This option allows you to set a prefix for URLs.
    | Useful for multiple apps using the same database instance.
    |
    */

    'prefix' => env('SHOPIFY_APP_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Shop Model
    |--------------------------------------------------------------------------
    |
    | This option is for overriding the shop model with your own.
    |
    */

    'shop_model' => env('SHOPIFY_SHOP_MODEL', '\OhMyBrew\ShopifyApp\Models\Shop'),

    /*
    |--------------------------------------------------------------------------
    | AppBridge Mode
    |--------------------------------------------------------------------------
    |
    | AppBridge (embedded apps) are enabled by default. Set to false to use legacy
    | mode and host the app inside your own container.
    |
    */

    'appbridge_enabled' => (bool) env('SHOPIFY_APPBRIDGE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Shopify App Name
    |--------------------------------------------------------------------------
    |
    | This option simply lets you display your app's name.
    |
    */

    'app_name' => env('SHOPIFY_APP_NAME', 'Shopify App'),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Version
    |--------------------------------------------------------------------------
    |
    | This option is for the app's API version string.
    | Use "YYYY-MM" or "unstable". Refer to Shopify's documentation
    | on API versioning for the current stable version.
    |
    */

    'api_version' => env('SHOPIFY_API_VERSION', null),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Key
    |--------------------------------------------------------------------------
    |
    | This option is for the app's API key.
    |
    */

    'api_key' => env('SHOPIFY_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Secret
    |--------------------------------------------------------------------------
    |
    | This option is for the app's API secret.
    |
    */

    'api_secret' => env('SHOPIFY_API_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Scopes
    |--------------------------------------------------------------------------
    |
    | This option is for the scopes your application needs in the API.
    |
    */

    'api_scopes' => env('SHOPIFY_API_SCOPES', 'read_products,write_products'),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Grant Mode
    |--------------------------------------------------------------------------
    |
    | This option is for the grant mode when authenticating.
    | Default is "offline", "per-user" is available as well.
    | Note: Install will always be in offline mode.
    |
    */

    'api_grant_mode' => env('SHOPIFY_API_GRANT_MODE', 'offline'),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Redirect
    |--------------------------------------------------------------------------
    |
    | This option is for the redirect after authentication.
    |
    */

    'api_redirect' => env('SHOPIFY_API_REDIRECT', '/authenticate'),

    /*
    |--------------------------------------------------------------------------
    | Shopify API class
    |--------------------------------------------------------------------------
    |
    | This option option allows you to change out the default API class
    | which is OhMyBrew\BasicShopifyAPI. This option is mainly used for
    | testing and does not need to be changed unless required.
    |
    */

    'api_class' => env('SHOPIFY_API_CLASS', \OhMyBrew\BasicShopifyAPI::class),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | This option option allows you to enable basic rate limiting
    | for API calls using the default BasicShopifyAPI library.
    | Default is off.
    |
    */

    'api_rate_limiting_enabled' => env('SHOPIFY_API_RATE_LIMITING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Rate Limit Cycle
    |--------------------------------------------------------------------------
    |
    | This option option allows you to set the millisecond cycle for
    | API calls using the default BasicShopifyAPI library.
    | Default is 500ms per API call.
    | Example: 0.5 * 1000
    |
    */

    'api_rate_limit_cycle' => env('SHOPIFY_API_RATE_LIMIT_CYCLE', null),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Rate Limit Cycle Buffer
    |--------------------------------------------------------------------------
    |
    | This option option allows you to set the millisecond buffer for
    | API calls using the default BasicShopifyAPI library which gets
    | appended to the `api_rate_limit_cycle` value for a safety net.
    | Default is 100ms per API call.
    | Example: 0.1 * 1000
    |
    */

    'api_rate_limit_cycle_buffer' => env('SHOPIFY_API_RATE_LIMIT_CYCLE_BUFFER', null),

    /*
    |--------------------------------------------------------------------------
    | Shopify "MyShopify" domain
    |--------------------------------------------------------------------------
    |
    | The internal URL used by shops. This will not change but in the future
    | it may.
    |
    */

    'myshopify_domain' => env('SHOPIFY_MYSHOPIFY_DOMAIN', 'myshopify.com'),

    /*
    |--------------------------------------------------------------------------
    | Enable Billing
    |--------------------------------------------------------------------------
    |
    | Enable billing component to the package.
    |
    */

    'billing_enabled' => (bool) env('SHOPIFY_BILLING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Freemium Mode
    |--------------------------------------------------------------------------
    |
    | Allow a shop use the app in "freemium" mode.
    | Shop will get a `freemium` flag on their record in the table.
    |
    */

    'billing_freemium_enabled' => (bool) env('SHOPIFY_BILLING_FREEMIUM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Billing Redirect
    |--------------------------------------------------------------------------
    |
    | Required redirection URL for billing when
    | a customer accepts or declines the charge presented.
    |
    */

    'billing_redirect' => env('SHOPIFY_BILLING_REDIRECT', '/billing/process'),

    /*
    |--------------------------------------------------------------------------
    | Shopify Webhooks
    |--------------------------------------------------------------------------
    |
    | This option is for defining webhooks.
    | Key is for the Shopify webhook event
    | Value is for the endpoint to call
    |
    */

    'webhooks' => [
        /*
            [
                'topic' => env('SHOPIFY_WEBHOOK_1_TOPIC', 'orders/create'),
                'address' => env('SHOPIFY_WEBHOOK_1_ADDRESS', 'https://some-app.com/webhook/orders-create')
            ],
            ...
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Shopify ScriptTags
    |--------------------------------------------------------------------------
    |
    | This option is for defining scripttags.
    |
    */

    'scripttags' => [
        /*
            [
                'src' => env('SHOPIFY_SCRIPTTAG_1_SRC', 'https://some-app.com/some-controller/js-method-response'),
                'event' => env('SHOPIFY_SCRIPTTAG_1_EVENT', 'onload'),
                'display_scope' => env('SHOPIFY_SCRIPTTAG_1_DISPLAY_SCOPE', 'online_store')
            ],
            ...
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | After Authenticate Job
    |--------------------------------------------------------------------------
    |
    | This option is for firing a job after a shop has been authenticated.
    | This, like webhooks and scripttag jobs, will fire every time a shop
    | authenticates, not just once.
    |
    */

    'after_authenticate_job' => [
        /*
            [
                'job' => env('AFTER_AUTHENTICATE_JOB'), // example: \App\Jobs\AfterAuthenticateJob::class
                'inline' => env('AFTER_AUTHENTICATE_JOB_INLINE', false) // False = dispatch job for later, true = dispatch immediately
            ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Queues
    |--------------------------------------------------------------------------
    |
    | This option is for setting a specific job queue for webhooks, scripttags
    | and after_authenticate_job.
    |
    */

    'job_queues' => [
        'webhooks'           => env('WEBHOOKS_JOB_QUEUE', null),
        'scripttags'         => env('SCRIPTTAGS_JOB_QUEUE', null),
        'after_authenticate' => env('AFTER_AUTHENTICATE_JOB_QUEUE', null),
    ],
];
