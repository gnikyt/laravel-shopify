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
    | This option allows you to use:
    | `php artisan vendor:publish --tag=shopify-migrations` to push migrations
    | to your app's folder so you're free to modify before migrating.
    |
    */
    'manual_migrations' => (bool) env('SHOPIFY_MANUAL_MIGRATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Manual routes
    |--------------------------------------------------------------------------
    |
    | This option allows you to ignore the package's built-in routes.
    | Use `false` (default) for allowing the built-in routes. Otherwise, you
    | can list out which route "names" you would like excluded.
    | See `resources/routes/shopify.php` and `resources/routes/api.php`
    | for a list of available route names.
    | Example: `home,billing` would ignore both "home" and "billing" routes.
    |
    | Please note that if you override the route names (see "route_names" below),
    | the route names that are used in this option DO NOT change!
    |
    */
    'manual_routes' => env('SHOPIFY_MANUAL_ROUTES', false),

    /*
    |--------------------------------------------------------------------------
    | Route names
    |--------------------------------------------------------------------------
    |
    | This option allows you to override the package's built-in route names.
    | This can help you avoid collisions with your existing route names.
    |
    */
    'route_names' => [
        'home'                 => env('SHOPIFY_ROUTE_NAME_HOME', 'home'),
        'authenticate'         => env('SHOPIFY_ROUTE_NAME_AUTHENTICATE', 'authenticate'),
        'authenticate.oauth'   => env('SHOPIFY_ROUTE_NAME_AUTHENTICATE_OAUTH', 'authenticate.oauth'),
        'billing'              => env('SHOPIFY_ROUTE_NAME_BILLING', 'billing'),
        'billing.process'      => env('SHOPIFY_ROUTE_NAME_BILLING_PROCESS', 'billing.process'),
        'billing.usage_charge' => env('SHOPIFY_ROUTE_NAME_BILLING_USAGE_CHARGE', 'billing.usage_charge'),
        'webhook'              => env('SHOPIFY_ROUTE_NAME_WEBHOOK', 'webhook'),
        'itp'                  => env('SHOPIFY_ROUTE_NAME_ITP', 'itp'),
        'itp.ask'              => env('SHOPIFY_ROUTE_NAME_ITP_ASK', 'itp.ask'),
    ],

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
    | This option allows you to change out the default job namespace
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
    | The first param injected in is the current options (\Osiset\BasicShopifyAPI\Options).
    | The second param injected in is the session (if available) (\Osiset\BasicShopifyAPI\Session).
    | The third param injected in is the current request input/query array (\Illuminate\Http\Request::all()).
    | With all this, you can customize the options, change params, and more.
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

    /*
    |--------------------------------------------------------------------------
    | Config API Callback
    |--------------------------------------------------------------------------
    |
    | This option can be used to modify what returns when `getConfig('api_*')` is used.
    | A use-case for this is modifying the return of `api_secret` or something similar.
    |
    | A closure/callable is required.
    | The first argument will be the key string.
    | The second argument will be something to help identify the shop.
    |
    */

    'config_api_callback' => null,
];
