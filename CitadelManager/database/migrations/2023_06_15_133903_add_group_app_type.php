<?php

use App\Group;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGroupAppType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_group_to_app_groups', function ($table) {
            $table->tinyInteger('filter_type')->index();
        });
        $groups = Group::all();
        foreach ($groups as $group) {
            $appConfig = json_decode($group->app_cfg, true);
            $filterType = \App\UserGroupToAppGroup::FILTER_TYPE_WHITELIST;
            if (isset($app_cfg['Whitelist'])) {
                $filterType = \App\UserGroupToAppGroup::FILTER_TYPE_WHITELIST;
            } elseif (isset($appConfig['Blacklist'])) {
                $filterType = \App\UserGroupToAppGroup::FILTER_TYPE_BLACKLIST;
            } elseif (isset($appConfig['Blocklist'])) {
                $filterType = \App\UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS;
            }
            \App\UserGroupToAppGroup::where("user_group_id", $group->id)->update(["filter_type"=>$filterType]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_group_to_app_groups', function ($table) {
            $table->dropColumn('filter_type');

        });
    }
}
