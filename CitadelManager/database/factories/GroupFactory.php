<?php

use Faker\Generator as Faker;

$factory->define(App\Group::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'app_cfg' => json_encode([
            'UpdateFrequency' => $faker->numberBetween(30, 180),
            'PrimaryDns' => '198.58.111.139',
            'SecondaryDns' => '104.248.104.66',
            'CannotTerminate' => $faker->randomElement([true, false]),
            'BlockInternet' => $faker->randomElement([true, false]),
            'UseThreshold' => $faker->randomElement([true, false]),
            'ThresholdLimit' => 3,
            'ThresholdTriggerPeriod' => 3,
            'ThresholdTimeoutPeriod' => 3,
            'BypassesPermitted' => $faker->numberBetween(1, 10),
            'BypassDuration' => $faker->numberBetween(10, 60),
            'NlpThreshold' => NULL,
            'MaxTextTriggerScanningSize' => 3,
            'UpdateChannel' => 'Stable',
            'ReportLevel' => 0,
            $faker->randomElement(['Whitelist', 'Blacklist']) => 'checked',
        ]),
        'data_sha1' => sha1($faker->word),
        'notes' => $faker->text,
    ];
});
