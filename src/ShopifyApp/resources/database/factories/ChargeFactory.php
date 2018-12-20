<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Models\Charge;

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
    'type' => Charge::CHARGE_RECURRING,
]);

$factory->state(Charge::class, 'type_onetime', [
    'type' => Charge::CHARGE_ONETIME,
]);

$factory->state(Charge::class, 'type_usage', [
    'type' => Charge::CHARGE_USAGE,
]);

$factory->state(Charge::class, 'type_credit', [
    'type' => Charge::CHARGE_CREDIT,
]);

$factory->state(Charge::class, 'trial', function ($faker) {
    $days = $faker->numberBetween(7, 14);

    return [
        'trial_days'    => $days,
        'trial_ends_on' => Carbon::today()->addDays($days),
    ];
});
