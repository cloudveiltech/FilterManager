<?php

use Faker\Generator as Faker;

$factory->define(App\SystemPlatform::class, function (Faker $faker) {
    return [
        'platform' => $faker->randomElement(['WIN', 'OSX', 'LINUX']),
        'os_name' => $faker->word,
    ];
});
