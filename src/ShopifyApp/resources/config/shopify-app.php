<?php

return [
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
                'address' => env('SHOPIFY_WEBHOOK_1_ADDRESS', 'https://some-app.com/webhooks/orders/create')
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
    ]
];
