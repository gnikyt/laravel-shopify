<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Config;

$model = Config::get('auth.providers.users.model');

$factory->define($model, function (Faker $faker) {
    return [
        'name'     => "{$faker->domainWord}.myshopify.com",
        'password' => str_replace('-', '', $faker->uuid),
        'email'    => '',
    ];
});

$factory->state($model, 'freemium', [
    'shopify_freemium' => true,
]);

$factory->state($model, 'grandfathered', [
    'shopify_grandfathered' => true,
]);
