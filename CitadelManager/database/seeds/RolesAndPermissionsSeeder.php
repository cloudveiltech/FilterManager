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
        $user = Role::firstOrCreate(['name' => 'user', 'display_name' => 'Application User', 'description' => 'User is a registered application user.']);

        $timeRestrictions = Permission::firstOrCreate([
            'name' => 'can-edit-own-time-restrictions',
            'display_name' => 'Can Edit Time Restrictions',
            'description' => 'User is allowed to edit his own time restrictions settings'
        ]);

        $relaxedPolicyPassword = Permission::firstOrCreate([
            'name' => 'can-set-relaxed-policy-password',
            'display_name' => 'Relaxed Policy Password',
            'description' => 'User is allowed to set his own relaxed policy password.'
        ]);

        $addSelfModerated = Permission::firstOrCreate([
            'name' => 'add-self-moderated',
            'display_name' => 'Add Self-moderated Site Entries',
            'description' => 'User is allowed to delete entries from his self-moderation list'
        ]);

        $user->detachPermissions();
        $user->attachPermission($timeRestrictions);
        $user->attachPermission($relaxedPolicyPassword);
        $user->attachPermission($addSelfModerated);
        
        $owner = Role::firstOrCreate([
            'name' => "business-owner",
            'display_name' => "Business Owner",
            'description' => "User is a registered business owner and is allowed to manage all of his own settings."
        ]);

        $manageDeactivations = Permission::firstOrCreate([
            'name' => 'manage-deactivations',
            'display_name' => 'Manage Deactivations',
            'description' => 'User is allowed to approve and deny his own deactivation requests'
        ]);

        $deleteSelfModerated = Permission::firstOrCreate([
            'name' => 'delete-self-moderated',
            'display_name' => 'Delete Self-moderated Site Entries',
            'description' => 'User is allowed to delete entries from his self-moderation list',
        ]);

        $manageUserWhitelist = Permission::firstOrCreate([
            'name' => 'manage-whitelisted-sites',
            'display_name' => 'Manage User Whitelist',
            'description' => 'User is allowed to manage their own whitelist.',
        ]);

        $manageRelaxedPolicySettings = Permission::firstOrCreate([
            'name' => 'manage-relaxed-policy',
            'display_name' => 'Manage User Relaxed Policy',
            'description' => 'User is allowed to manage all of their own relaxed policy settings.',
        ]);

        $manageActivations = Permission::firstOrCreate([
            'name' => 'manage-own-activations',
            'display_name' => 'Manage Own Activations',
            'description' => 'User is allowed to manage their own activations.'
        ]);

        $owner->detachPermissions();
        $owner->attachPermission($timeRestrictions);
        $owner->attachPermission($relaxedPolicyPassword);
        $owner->attachPermission($addSelfModerated);
        $owner->attachPermission($manageDeactivations);
        $owner->attachPermission($deleteSelfModerated);
        $owner->attachPermission($manageUserWhitelist);
        $owner->attachPermission($manageRelaxedPolicySettings);
        $owner->attachPermission($manageActivations);
        
        $admin = Role::firstOrCreate([
            'name'         => 'admin',
            'display_name' => 'Administrator', // optional
            'description'  => 'User is allowed to manage and edit the settings for all other users'
        ]);
        
        $fullAdmin = Permission::firstOrCreate([
            'name'         => 'all',
            'display_name' => 'Full Admin Permissions', // optional        
            'description'  => 'Unrestricted access for Admins.' // optional
        ]);

        $admin->detachPermissions();
        $admin->attachPermission($fullAdmin);
    }
}
