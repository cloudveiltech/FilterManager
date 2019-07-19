<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Providers\Socialite\CloudVeilProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // This is so that our string db fields can fit with a UTF8 encoding.
        Schema::defaultStringLength(191);

        $this->bootCloudVeilSocialite();
    }

    private function bootCloudVeilSocialite()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'cloudveil',
            function ($app) use ($socialite) {
                $config = $app['config']['services.cloudveil'];
                return $socialite->buildProvider(CloudVeilProvider::class, $config);
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
