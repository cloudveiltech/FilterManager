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

        $timeRestrictions = new Permission();
        $timeRestrictions->name = 'can-edit-own-time-restrictions';
        $timeRestrictions->display_name = 'Can Edit Time Restrictions';
        $timeRestrictions->description = 'User is allowed to edit his own time restrictions settings';
        $timeRestrictions->save();

        $relaxedPolicyPassword = new Permission();
        $relaxedPolicyPassword->name = 'can-set-relaxed-policy-password';
        $relaxedPolicyPassword->display_name = 'Relaxed Policy Password';
        $relaxedPolicyPassword->description = 'User is allowed to set his own relaxed policy password.';
        $relaxedPolicyPassword->save();

        $deleteSelfModerated = new Permission();
        $deleteSelfModerated->name = 'delete-self-moderated';
        $deleteSelfModerated->display_name = 'Delete Self-moderated Site Entries';
        $deleteSelfModerated->description = 'User is allowed to delete entries from his self-moderation list';
        $deleteSelfModerated->save();

        $owner->attachPermission($timeRestrictions);
        $owner->attachPermission($relaxedPolicyPassword);
        $owner->attachPermission($deleteSelfModerated);
        
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
