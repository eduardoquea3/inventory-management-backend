<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Nothing here.
    }

    public function boot()
    {
        if (class_exists(Telescope::class)) {
            Telescope::auth(fn () => app()->environment('local'));
            Telescope::hideRequestParameters(['password', 'password_confirmation', '_token']);
            Telescope::hideRequestHeaders(['authorization', 'cookie', 'set-cookie', 'x-csrf-token', 'x-xsrf-token']);
        }
    }
}
