<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;

$factory->define(Charge::class, function (Faker $faker) {
    return [
        'charge_id' => $faker->randomNumber(8),
        'name'      => $faker->word,
        'price'     => $faker->randomFloat(),
    ];
});

$factory->state(Charge::class, 'test', [
    'test' => true,
]);

$factory->state(Charge::class, 'type_recurring', [
    'type' => ChargeType::RECURRING()->toNative(),
]);

$factory->state(Charge::class, 'type_onetime', [
    'type' => ChargeType::ONETIME()->toNative(),
]);

$factory->state(Charge::class, 'type_usage', [
    'type' => ChargeType::USAGE()->toNative(),
]);

$factory->state(Charge::class, 'type_credit', [
    'type' => ChargeType::CREDIT()->toNative(),
]);

$factory->state(Charge::class, 'trial', function ($faker) {
    $days = $faker->numberBetween(7, 14);

    return [
        'trial_days'    => $days,
        'trial_ends_on' => Carbon::today()->addDays($days),
    ];
});
