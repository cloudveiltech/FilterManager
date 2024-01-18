<?php

use Faker\Generator as Faker;

$factory->define(App\FilterList::class, function (Faker $faker) {
    return [
        'namespace' => 'default',
        'category' => $faker->word . '_' . $faker->word,
        'type' => $faker->randomElement(['Filters', 'Triggers']),
        'file_sha1' => sha1($faker->word),
        'entries_count' => $faker->numberBetween(1, 10000),
    ];
});
