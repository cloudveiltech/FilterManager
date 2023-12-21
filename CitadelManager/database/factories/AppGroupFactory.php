<?php

use Faker\Generator as Faker;

$factory->define(App\AppGroup::class, function (Faker $faker) {
    return [
        'group_name' => $faker->word . ' App Group',
    ];
});
