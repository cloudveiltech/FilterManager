<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Group;

class GroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('groups')->insert([
            'name' => 'Default',
            'app_cfg' => '{}',
            'isactive' => true,
            'data_sha1' => 'null',
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

        factory(App\Group::class, 10)
            ->create();
    }
}
