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

        Validator::replacer('pwned', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':min', array_shift($parameters) ?? 1, $message);
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
