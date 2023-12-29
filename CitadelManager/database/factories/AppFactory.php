<?php

use Faker\Generator as Faker;

$factory->define(App\App::class, function (Faker $faker) {
    return [
        'name' => $faker->word . '.exe',
        'notes' => $faker->sentence,
        'platform_name' => $faker->randomElement(['WIN', 'OSX']),
    ];
});
