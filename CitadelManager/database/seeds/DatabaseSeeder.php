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
        $this->call(AdminUserSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(GroupsTableSeeder::class);
    }
}
