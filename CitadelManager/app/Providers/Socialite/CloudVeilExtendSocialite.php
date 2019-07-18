<?php

namespace App\Providers\Socialite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CloudVeilExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('cloudveil', 'App\Providers\Socialite\CloudVeilProvider');
    }
}
