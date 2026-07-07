<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use Zizaco\Entrust\EntrustRole;

/**
 * Description of Role
 *
 */
class Role extends EntrustRole
{
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }
}
