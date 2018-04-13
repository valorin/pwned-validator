<?php
namespace Valorin\Pwned;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('pwned', Pwned::class);

        Validator::replacer('count', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':count', $attribute.' - '.$rule.' - '.implode(',', $parameters), $message);
        });
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
