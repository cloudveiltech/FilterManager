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
        $roles = App\Role::all();

        factory(App\User::class, 30)
            ->create()
            ->each(function ($user) use($roles) {
                factory(App\AppUserActivation::class, 3)->create(["user_id" => $user->id]);
                $user->roles()->attach($roles->random());
            });
    }
}
