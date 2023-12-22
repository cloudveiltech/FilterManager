<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Order is important here!
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(GroupsTableSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(FilterListSeeder::class);
        $this->call(GroupFilterAssignmentSeeder::class);
        $this->call(AppGroupSeeder::class);
        $this->call(AppSeeder::class);
        $this->call(AppGroupToAppSeeder::class);
        $this->call(UserGroupAppGroupSeeder::class);
        $this->call(SystemPlatformVersionSeeder::class);
    }
}
