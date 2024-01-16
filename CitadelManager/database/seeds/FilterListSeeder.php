<?php

use Illuminate\Database\Seeder;

class FilterListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\FilterList::class, 40)
            ->create();
    }
}
