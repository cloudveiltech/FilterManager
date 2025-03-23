<?php

namespace Database\Factories;

use App\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'app_cfg' => json_encode([
                'UpdateFrequency' => $this->faker->numberBetween(30, 180),
                'PrimaryDns' => '198.58.111.139',
                'SecondaryDns' => '104.248.104.66',
                'CannotTerminate' => $this->faker->randomElement([true, false]),
                'BlockInternet' => $this->faker->randomElement([true, false]),
                'UseThreshold' => $this->faker->randomElement([true, false]),
                'ThresholdLimit' => 3,
                'ThresholdTriggerPeriod' => 3,
                'ThresholdTimeoutPeriod' => 3,
                'BypassesPermitted' => $this->faker->numberBetween(1, 10),
                'BypassDuration' => $this->faker->numberBetween(10, 60),
                'NlpThreshold' => NULL,
                'MaxTextTriggerScanningSize' => 3,
                'UpdateChannel' => 'Stable',
                'ReportLevel' => 0,
                $this->faker->randomElement(['Whitelist', 'Blacklist']) => 'checked',
            ]),
            'data_sha1' => sha1($this->faker->word),
            'notes' => $this->faker->text,
        ];
    }
}
