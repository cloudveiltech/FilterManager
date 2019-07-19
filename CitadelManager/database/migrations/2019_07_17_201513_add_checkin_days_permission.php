<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCheckinDaysPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $owner = DB::table('roles')->where('name', 'business-owner')->first();
        
        if($owner) {
            $manageCheckinDaysPermissionId = DB::table('permissions')->insertGetId([
                'name' => 'manage-checkin-days',
                'display_name' => 'Can Edit Offline Report Timeout Days',
                'description' => 'User is allowed to edit the offline reporting timeout for his activations'
            ]);

            DB::table('permission_role')->insert(['role_id' => $owner->id, 'permission_id' => $manageCheckinDaysPermissionId]);
        } else {
            echo "No business-owner role found. Please check your database.\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Roll back manage check-in days.
        $manageCheckinDaysPermission = DB::table('permissions')->where('name', 'manage-checkin-days')->first();

        if($manageCheckinDaysPermission) {
            DB::table('permission_role')
                ->where('permission_id', $manageCheckinDaysPermission->id)
                ->delete();
        } else {
            echo "No manage-checkin-days permission found. Please check your database. You may want to manually delete this migration.\n";
        }
    }
}
