<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class UserGroupAppGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $appGroups = App\AppGroup::all();

        $data = [];

        App\Group::all()->each(function ($group) use ($appGroups, &$data) {
            $appGroups->random(rand(1, 10))->each(function ($appGroup) use ($group, &$data) {
                $data[] = [
                    'user_group_id' => $group->id,
                    'app_group_id' => $appGroup->id,
                    'filter_type' => rand(0, 2)
                ];
            });
        });

        DB::table('user_group_to_app_groups')->insert($data);
    }
}
