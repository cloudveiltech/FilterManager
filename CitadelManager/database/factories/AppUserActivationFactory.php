<?php

use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(App\AppUserActivation::class, function (Faker $faker) {
    return [
        'identifier' => sha1($faker->word),
        'device_id' => Str::upper($faker->word),
        'ip_address' => $faker->ipv4,
        'bypass_quantity' => $faker->numberBetween(1, 10),
        'bypass_period' => $faker->numberBetween(10, 60),
        'bypass_used' => $faker->numberBetween(0, 10),
        'app_version' => $faker->numberBetween(1, 99) . '.' . $faker->numberBetween(0, 99) . '.' . $faker->numberBetween(0, 99),
        'report_level' => $faker->numberBetween(0, 1),
        'check_in_days' => $faker->numberBetween(6, 10),
        'alert_partner' => 0,
        'last_update_requested_time' => $faker->dateTimeBetween('-1 year', 'now'),
        'last_sync_time' => $faker->dateTimeBetween('-1 year', 'now'),
        'platform_name' => $faker->randomElement(['WIN', 'OSX']),
        'group_id' => $faker->randomElement([1, -1]),
        'friendly_name' => $faker->randomElement(['', $faker->word]),
        'os_version' => $faker->numberBetween(10, 99) . '.' . $faker->numberBetween(0, 9) . '.' . $faker->numberBetween(0, 99999),
    ];
});
