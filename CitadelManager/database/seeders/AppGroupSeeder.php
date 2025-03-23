<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class AppGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\AppGroup::class, 15)
            ->create();
    }
}
