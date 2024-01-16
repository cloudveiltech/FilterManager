<?php

use App\DeactivationRequest;
use Illuminate\Database\Seeder;

class DeactivationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $activations = App\AppUserActivation::all()->random(6);

        $activations->each(function($activation) {
            DeactivationRequest::create([
                'user_id' => $activation->user_id,
                'identifier' => $activation->identifier,
                'device_id' => $activation->device_id,
                'granted' => rand(0, 1),
            ]);
        });

    }
}
