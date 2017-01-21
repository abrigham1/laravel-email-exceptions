<?php

namespace Abrigham\LaravelEmailExceptions;

use Illuminate\Support\ServiceProvider;

class EmailExceptionsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'laravelEmailExceptions');

        $this->publishes([
            __DIR__.'/config/laravelEmailExceptions.php' => config_path('laravelEmailExceptions.php'),
        ], 'config');
        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/laravelEmailExceptions'),
        ], 'views');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/laravelEmailExceptions.php',
            'laravelEmailExceptions'
        );
    }
}
