<?php

use Illuminate\Database\Seeder;

class SystemExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fileExtensions = [
            [
                'sys_name' => 'WIN',
                'file_extensions' => '.msi,.exe',
            ],
            [
                'sys_name' => 'LINUX',
                'file_extensions' => '.run',
            ],
            [
                'sys_name' => 'MAC',
                'file_extensions' => '.dmg',
            ],
        ];

        DB::table('file_extensions')->insert($fileExtensions);
    }
}
