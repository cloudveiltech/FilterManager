<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppUserActivation extends Model
{
    
    protected $fillable = [
        'identifier', 'device_id', 'user_id','ip_address','bypass_quantity','bypass_period','bypass_used'
    ];
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
