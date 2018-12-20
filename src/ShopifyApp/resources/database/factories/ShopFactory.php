<?php

use Faker\Generator as Faker;
use OhMyBrew\ShopifyApp\Models\Shop;

$factory->define(Shop::class, function (Faker $faker) {
    return [
        'shopify_domain' => "{$faker->domainWord}.myshopify.com",
        'shopify_token'  => str_replace('-', '', $faker->uuid),
    ];
});

$factory->state(Shop::class, 'freemium', [
    'freemium' => true,
]);

$factory->state(Shop::class, 'grandfathered', [
    'grandfathered' => true,
]);
