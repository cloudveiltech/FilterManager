<?php

use Faker\Generator as Faker;

$factory->define(App\User::class, function (Faker $faker) {
    static $password;
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'group_id' => 1,
        'isactive' => 1,
        'report_level' => $faker->numberBetween(0, 1),
        'activations_allowed' => $faker->numberBetween(0, 10),
        'relaxed_policy_passcode' => $faker->word,
        'enable_relaxed_policy_passcode' => $faker->numberBetween(0, 1),
    ];
});
