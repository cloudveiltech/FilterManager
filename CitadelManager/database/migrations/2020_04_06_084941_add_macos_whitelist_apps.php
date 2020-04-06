<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMacosWhitelistApps extends Migration
{
    private $apps = ["com.apple.mail",
        "com.apple.Maps",
        "com.apple.iCal",
        "com.apple.Photos",
        "com.apple.iChat",
        "com.apple.iWork.Pages",
        "com.apple.iWork.Numbers",
        "com.apple.iWork.Keynote",
        "com.apple.Notes",
        "com.apple.RemoteDesktop",
        "com.apple.news",
        "com.apple.podcasts",
        "com.apple.iBooksX",
        "com.apple.findmy",
        "com.apple.Home",
        "com.microsoft.Excel",
        "com.microsoft.Word",
        "com.microsoft.Powerpoint",
        "com.microsoft.rdc.macos",
        "com.microsoft.rdc.macos",
        "com.tapbots.Pastebot2Mac",
        "com.tinyspeck.slackmacgap",
        "com.timecamp.TimeCamp",
        "com.devon-technologies.think3",
        "com.getharvest.harvestx",
        "com.tdesktop.Telegram",
        "ru.keepcoder.Telegram",
        "com.tlphn.Telephone",
        "com.teamviewer.TeamViewer",
        "us.zoom.xos",
        "com.agilebits.onepassword7",
        "com.lastpass.LastPass",
        "com.getdropbox.dropbox",
        "com.apple.dt.Xcode",
        "com.intuit.QBOClient",
        "WhatsApp",
        "com.parallels.desktop.console",
        "com.vmware.fusion",
        "com.fujitsu.pfu.ScanSnapHome",
        "com.olivetree.BibleReaderMac",
        "com.google.GoogleDrive",
        "org.filezilla-project.filezilla.sandbox",
        "com.ookla.speedtest-macos",
        "notion.id",
        "com.monday.desktop",
        "com.GroundControl.GroundControl-Launchpad",
        "com.squirrels.Reflector-2"];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $values = [];
        foreach ($this->apps as $bundleId) {
            $values[] = "('$bundleId', 'Mac OS imported whitelist app', 'OSX', NOW(), NOW())";
        }
        $statement = "INSERT INTO apps( name, notes, platform_name, created_at, updated_at) VALUES " . implode(",", $values);
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DELETE FROM apps WHERE platform_name='OSX' AND notes ='Mac OS imported whitelist app'");
    }
}
