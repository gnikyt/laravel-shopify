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
    | `php artisan vendor:publish --tag=shopify-migrations` to push migrations
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
    | Shopify Jobs Namespace
    |--------------------------------------------------------------------------
    |
    | This option option allows you to change out the default job namespace
    | which is \App\Jobs. This option is mainly used if any custom configuration
    | is done in autoload and does not need to be changed unless required.
    |
    */
    'job_namespace' => env('SHOPIFY_JOB_NAMESPACE', '\\App\\Jobs\\'),

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
    | AppBridge Mode
    |--------------------------------------------------------------------------
    |
    | AppBridge (embedded apps) are enabled by default. Set to false to use legacy
    | mode and host the app inside your own container.
    |
    */

    'appbridge_enabled' => (bool) env('SHOPIFY_APPBRIDGE_ENABLED', true),

    // Use semver range to link to a major or minor version number.
    // Leaving empty will use the latest verison - not recommended in production.
    'appbridge_version' => env('SHOPIFY_APPBRIDGE_VERSION', '1'),

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

    'api_version' => env('SHOPIFY_API_VERSION', '2020-01'),

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
    | Default is "OFFLINE", "PERUSER" is available as well.
    | Note: Install will always be in offline mode.
    |
    */

    'api_grant_mode' => env('SHOPIFY_API_GRANT_MODE', 'OFFLINE'),

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
    | Shopify API Time Store
    |--------------------------------------------------------------------------
    |
    | This option is for the class which will hold the timestamps for API calls.
    |
    */

    'api_time_store' => env('SHOPIFY_API_TIME_STORE', \Osiset\BasicShopifyAPI\Store\Memory::class),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Limit Store
    |--------------------------------------------------------------------------
    |
    | This option is for the class which will hold the call limits for REST and GraphQL.
    |
    */

    'api_limit_store' => env('SHOPIFY_API_LIMIT_STORE', \Osiset\BasicShopifyAPI\Store\Memory::class),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Deferrer
    |--------------------------------------------------------------------------
    |
    | This option is for the class which will handle sleep deferrals for API calls.
    |
    */

    'api_deferrer' => env('SHOPIFY_API_DEFERRER', \Osiset\BasicShopifyAPI\Deferrers\Sleep::class),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Init Function
    |--------------------------------------------------------------------------
    |
    | This option is for initing the BasicShopifyAPI package optionally yourself.
    | The first param injected in the current options (\Osiset\BasicShopifyAPI\Options).
    | With this, you can customize the options, change params, and more.
    |
    | Value for this option must be a callable (callable, Closure, etc).
    |
    */

    'api_init' => null,

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
                'job' => env('AFTER_AUTHENTICATE_JOB'), // example: \App\Jobs\AfterAuthorizeJob::class
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
