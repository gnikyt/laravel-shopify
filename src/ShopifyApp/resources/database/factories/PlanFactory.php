<?php

use Faker\Generator as Faker;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;

$factory->define(Plan::class, function (Faker $faker) {
    return [
        'name'  => $faker->word,
        'price' => $faker->randomFloat(),
    ];
});

$factory->state(Plan::class, 'usage', function ($faker) {
    return [
        'capped_amount' => $faker->randomFloat(),
        'terms'         => $faker->sentence,
    ];
});

$factory->state(Plan::class, 'trial', function ($faker) {
    return [
        'trial_days' => $faker->numberBetween(7, 14),
    ];
});

$factory->state(Plan::class, 'test', [
    'test' => true,
]);

$factory->state(Plan::class, 'installable', [
    'on_install' => true,
]);

$factory->state(Plan::class, 'type_recurring', [
    'type' => PlanType::RECURRING()->toNative(),
]);

$factory->state(Plan::class, 'type_onetime', [
    'type' => PlanType::ONETIME()->toNative(),
]);
