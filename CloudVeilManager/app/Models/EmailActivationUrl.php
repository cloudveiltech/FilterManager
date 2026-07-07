<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use PharIo\Manifest\Email;
use Zizaco\Entrust\EntrustRole;

/**
 * Description of Role
 *
 */
class EmailActivationUrl extends Model {
    public $timestamps = true;
    protected $primaryKey = 'hash';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hash', 'login_request', 'expired_at',
    ];

    protected $casts = [
        'login_request' => 'array',
    ];

    public function isExpired() {
        return Carbon::now()->greaterThan($this->expired_at);
    }

    public static function removeExpiredUrls() {
        EmailActivationUrl::where("expired_at", "<",  Carbon::now())->delete();
    }
}
