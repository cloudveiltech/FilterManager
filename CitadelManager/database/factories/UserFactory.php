<?php

namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $password;
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password ?: $password = bcrypt('secret'),
            'remember_token' => Str::random(10),
            'group_id' => 1,
            'isactive' => 1,
            'report_level' => $this->faker->numberBetween(0, 1),
            'activations_allowed' => $this->faker->numberBetween(0, 10),
            'relaxed_policy_passcode' => $this->faker->word,
            'enable_relaxed_policy_passcode' => $this->faker->numberBetween(0, 1),
        ];
    }
}
