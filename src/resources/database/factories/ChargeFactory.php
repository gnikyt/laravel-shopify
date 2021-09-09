<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Util;

$chargeModel = Util::getShopifyConfig('models.charge', Charge::class);

$factory->define($chargeModel, function (Faker $faker) {
    return [
        'charge_id' => $faker->randomNumber(8),
        'name' => $faker->word,
        'price' => $faker->randomFloat(),
        'status' => ChargeStatus::ACCEPTED()->toNative(),
    ];
});

$factory->state($chargeModel, 'test', [
    'test' => true,
]);

$factory->state($chargeModel, 'type_recurring', [
    'type' => ChargeType::RECURRING()->toNative(),
]);

$factory->state($chargeModel, 'type_onetime', [
    'type' => ChargeType::CHARGE()->toNative(),
]);

$factory->state($chargeModel, 'type_usage', [
    'type' => ChargeType::USAGE()->toNative(),
]);

$factory->state($chargeModel, 'type_credit', [
    'type' => ChargeType::CREDIT()->toNative(),
]);

$factory->state($chargeModel, 'trial', function ($faker) {
    $days = $faker->numberBetween(7, 14);

    return [
        'trial_days' => $days,
        'trial_ends_on' => Carbon::today()->addDays($days),
    ];
});
