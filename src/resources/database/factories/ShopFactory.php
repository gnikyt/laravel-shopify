<?php

use Faker\Generator as Faker;
use Osiset\ShopifyApp\Util;

$model = Util::getShopifyConfig('user_model');

$factory->define($model, function (Faker $faker) {
    return [
        'name' => "{$faker->domainWord}.myshopify.com",
        'password' => str_replace('-', '', $faker->uuid),
        'email' => $faker->email,
    ];
});

$factory->state($model, 'freemium', [
    'shopify_freemium' => true,
]);

$factory->state($model, 'grandfathered', [
    'shopify_grandfathered' => true,
]);
