<?php

use Faker\Generator as Faker;

$factory->define(App\SystemVersion::class, function (Faker $faker) {
    return [
        'app_name' => 'CloudVeil',
        'file_name' => $faker->randomElement(['CloudVeil', 'CloudVeilInstaller']),
        'version_number' => $faker->numberBetween(1, 9) . '.' . $faker->numberBetween(0, 9) . '.' . $faker->numberBetween(0, 99),
        'changes' => 'N/A',
        'alpha' => $faker->numberBetween(1, 9) . '.' . $faker->numberBetween(0, 9) . '.' . $faker->numberBetween(0, 99),
        'beta' => $faker->numberBetween(1, 9) . '.' . $faker->numberBetween(0, 9) . '.' . $faker->numberBetween(0, 99),
        'stable' => $faker->numberBetween(1, 9) . '.' . $faker->numberBetween(0, 9) . '.' . $faker->numberBetween(0, 99),
        'release_date' => $faker->dateTimeBetween('-1 year', 'now'),
        'active' => 1,
        'file_ext' => $faker->randomElement(['exe', 'msi', 'zip']),
    ];
});
