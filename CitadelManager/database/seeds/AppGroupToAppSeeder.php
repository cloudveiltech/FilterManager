<?php

use Illuminate\Database\Seeder;

class AppGroupToAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all the app groups attaching up to 3 random apps to each
        $appGroups = App\AppGroup::all();

        // Populate the pivot table
        App\App::all()->each(function ($app) use ($appGroups) {
            $appGroups->random(rand(1, 3))->each(function ($appGroup) use ($app) {
                $appGroup->app()->attach($app->id);
            });
        });
    }
}
