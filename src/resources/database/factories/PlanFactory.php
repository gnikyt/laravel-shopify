<?php

use Faker\Generator as Faker;
use Osiset\ShopifyApp\Objects\Enums\PlanInterval;
use Osiset\ShopifyApp\Objects\Enums\PlanType;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Util;

$planModel = Util::getShopifyConfig('models.plan', Plan::class);

$factory->define($planModel, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'price' => $faker->randomFloat(),
    ];
});

$factory->state($planModel, 'usage', function ($faker) {
    return [
        'capped_amount' => $faker->randomFloat(),
        'terms' => $faker->sentence,
    ];
});

$factory->state($planModel, 'trial', function ($faker) {
    return [
        'trial_days' => $faker->numberBetween(7, 14),
    ];
});

$factory->state($planModel, 'test', [
    'test' => true,
]);

$factory->state($planModel, 'installable', [
    'on_install' => true,
]);

$factory->state($planModel, 'type_recurring', [
    'type' => PlanType::RECURRING()->toNative(),
    'interval' => PlanInterval::EVERY_30_DAYS()->toNative(),
]);

$factory->state($planModel, 'type_onetime', [
    'type' => PlanType::ONETIME()->toNative(),
]);

$factory->state($planModel, 'interval_annual', [
    'interval' => PlanInterval::ANNUAL()->toNative(),
]);
