<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminName = env('ADMIN_NAME', null);
        $adminEmail = env('ADMIN_EMAIL', null);
        $adminPass = env('ADMIN_PASSWORD', null);
        
        $mustChange = false;
        
        if(is_null($adminName))
        {
            $adminName = str_random(10);
        }
        
        if(is_null($adminEmail))
        {
            $mustChange = true;
            $adminEmail = str_random(10) . '@localhost';
        }
        
        if(is_null($adminPass))
        {
            $mustChange = true;
            $adminPass = str_random(10);
        }       
        
        DB::table('users')->insert([
            'name' => $adminName,
            'email' => $adminEmail,
            'isactive' => true,
            'password' => bcrypt($adminPass),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        
        $adminRole = Role::where('name', 'admin')->first();
        
        $user = User::where('name', $adminName)->first();
        $user->attachRole($adminRole);
        
        echo "Created admin user $adminEmail with password '$adminPass'.\n";
        
        if($mustChange)
        {
            echo "You MUST change this!!!\n";
        }
    }
}
