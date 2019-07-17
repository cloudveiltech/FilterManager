<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BusinessOwnerRolesAndPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $timeRestrictionsId = DB::table('permissions')->insertGetId([
            'name' => 'can-edit-own-time-restrictions',
            'display_name' => 'Can Edit Time Restrictions',
            'description' => 'User is allowed to edit his own time restrictions settings'
        ]);

        $relaxedPolicyPasswordId = DB::table('permissions')->insertGetId([
            'name' => 'can-set-relaxed-policy-password',
            'display_name' => 'Relaxed Policy Password',
            'description' => 'User is allowed to set his own relaxed policy password.'
        ]);

        $deleteSelfModeratedId = DB::table('permissions')->insertGetId([
            'name' => 'delete-self-moderated',
            'display_name' => 'Delete Self-moderated Site Entries',
            'description' => 'User is allowed to delete entries from his self-moderation list',
        ]);

        $selfModeratedId = DB::table('permissions')->insertGetId([
            'name' => 'add-self-moderated',
            'display_name' => 'Add Self-moderated Site Entries',
            'description' => 'User is allowed to delete entries from his self-moderation list'
        ]);

        $manageOwnActivationsId = DB::table('permissions')->insertGetId([
            'name' => 'manage-own-activations',
            'display_name' => 'Manage Own Activations',
            'description' => 'User is allowed to manage their own activations.'
        ]);

        $user = DB::table('roles')->where('name', 'user')->first();
        DB::table('permission_role')->where('role_id', $user->id)->delete();

        $id = $user->id;

        DB::table('permission_role')->insert(['role_id' => $id, 'permission_id' => $timeRestrictionsId]);
        DB::table('permission_role')->insert(['role_id' => $id, 'permission_id' => $relaxedPolicyPasswordId]);
        DB::table('permission_role')->insert(['role_id' => $id, 'permission_id' => $selfModeratedId]);
        DB::table('permission_role')->insert(['role_id' => $id, 'permission_id' => $manageOwnActivationsId]);

        $ownerId = DB::table('roles')->insertGetId([
            'name' => "business-owner",
            'display_name' => "Business Owner",
            'description' => "User is a registered business owner and is allowed to manage all of his own settings."
        ]);

        $manageDeactivations = DB::table('permissions')->insertGetId([
            'name' => 'manage-deactivations',
            'display_name' => 'Manage Deactivations',
            'description' => 'User is allowed to approve and deny his own deactivation requests'
        ]);

        $manageUserWhitelist = DB::table('permissions')->insertGetId([
            'name' => 'manage-whitelisted-sites',
            'display_name' => 'Manage User Whitelist',
            'description' => 'User is allowed to manage their own whitelist.',
        ]);

        $manageRelaxedPolicySettings = DB::table('permissions')->insertGetId([
            'name' => 'manage-relaxed-policy',
            'display_name' => 'Manage User Relaxed Policy',
            'description' => 'User is allowed to manage all of their own relaxed policy settings.',
        ]);

        $deleteOrBlockActivations = DB::table('permissions')->insertGetId([
            'name' => 'delete-activations',
            'display_name' => 'Revoke Activations',
            'description' => 'User is allowed to delete or block their activations.'
        ]);

        $setActivationReportLevel = DB::table('permissions')->insertGetId([
            'name' => 'set-activation-report-level',
            'display_name' => 'Set Activation Report Level',
            'description' => 'User is allowed to set report levels for their activations.'
        ]);

        DB::table('permission_role')->where('role_id', $ownerId)->delete();
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $timeRestrictionsId]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $relaxedPolicyPasswordId]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $selfModeratedId]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $manageDeactivations]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $manageUserWhitelist]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $manageRelaxedPolicySettings]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $manageOwnActivationsId]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $deleteOrBlockActivations]);
        DB::table('permission_role')->insert(['role_id' => $ownerId, 'permission_id' => $setActivationReportLevel]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Roll back business owner permission role relationships
        $owner = DB::table('roles')->where('name', 'business-owner')->first();

        if($owner) {
            DB::table('permission_role')->where('role_id', $owner->id)->delete();
        }

        DB::table('roles')->where('name', 'business-owner')->delete();

        // Roll back business owner permissions
        DB::table('permissions')->where('name', 'can-edit-own-time-restrictions')->delete();
        DB::table('permissions')->where('name', 'can-set-relaxed-policy-password')->delete();
        DB::table('permissions')->where('name', 'delete-self-moderated')->delete();
        DB::table('permissions')->where('name', 'manage-deactivations')->delete();
        DB::table('permissions')->where('name', 'add-self-moderated')->delete();
        DB::table('permissions')->where('name', 'manage-own-activations')->delete();
        DB::table('permissions')->where('name', 'manage-whitelisted-sites')->delete();
        DB::table('permissions')->where('name', 'manage-relaxed-policy')->delete();
        DB::table('permissions')->where('name', 'delete-activations')->delete();
        DB::table('permissions')->where('name', 'set-activation-report-level')->delete();
    }
}
