<?php
namespace App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TwoFactorCode extends Model
{
    const EXPIRATION_INTERVAL_HR = 1;
    public $timestamps = true;

    protected $fillable = [
        'id', 'code', 'user_id', 'expired_at'
    ];

    public static function removeExpiredCodes() {
        TwoFactorCode::where("expired_at", "<",  Carbon::now())->delete();
    }
    public static function dropCodesForUserId($userId) {
        TwoFactorCode::where("user_id", $userId)->delete();
    }

}
