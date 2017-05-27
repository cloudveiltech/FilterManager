<?php

use Illuminate\Database\Seeder;
use App\Role;
use App\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $owner = new Role();
        $owner->name         = 'user';
        $owner->display_name = 'Application User'; // optional
        $owner->description  = 'User is a registered application user.';
        $owner->save();

        $admin = new Role();
        $admin->name         = 'admin';
        $admin->display_name = 'Administrator'; // optional
        $admin->description  = 'User is allowed to manage and edit the settings for all other users';
        $admin->save();
        
        $fullAdmin = new Permission();
        $fullAdmin->name         = 'all';
        $fullAdmin->display_name = 'Full Admin Permissions'; // optional        
        $fullAdmin->description  = 'Unrestricted access for Admins.'; // optional
        $fullAdmin->save();

        $admin->attachPermission($fullAdmin);
    }
}
