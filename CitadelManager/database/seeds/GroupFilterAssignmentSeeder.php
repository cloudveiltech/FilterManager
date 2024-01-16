<?php

use App\GroupFilterAssignment;
use Illuminate\Database\Seeder;

class GroupFilterAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $groups = App\Group::all();
        $filterLists = App\FilterList::all();
        // For each group, assign a random number of filter lists
        $groups->each(function ($group) use ($faker, $filterLists) {
            $numFilterLists = $faker->numberBetween(0, $filterLists->count());
            $filterListsToAssign = $filterLists->random($numFilterLists);

            $filterListsToAssign->each(function ($filterList) use ($group) {
                $optionsArray = [0, 0, 1];
                shuffle($optionsArray);

                GroupFilterAssignment::updateOrCreate([
                    'group_id' => $group->id,
                    'filter_list_id' => $filterList->id,
                    'as_blacklist' => $optionsArray[0],
                    'as_whitelist' => $optionsArray[1],
                    'as_bypass' => $optionsArray[2],
                ]);

            });
        });
    }
}
