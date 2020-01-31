<?php

use League\Flysystem\Config;
use Faker\Generator as Faker;

$model = Config::get('auth.providers.users.model');

$factory->define($model, function (Faker $faker) {
    return [
        'shopify_domain' => "{$faker->domainWord}.myshopify.com",
        'shopify_token'  => str_replace('-', '', $faker->uuid),
    ];
});

$factory->state($model, 'freemium', [
    'shopify_freemium' => true,
]);

$factory->state($model, 'grandfathered', [
    'shopify_grandfathered' => true,
]);
