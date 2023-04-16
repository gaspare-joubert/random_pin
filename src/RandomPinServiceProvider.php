<?php

namespace GaspareJoubert\RandomPin;

use Illuminate\Support\ServiceProvider;

class RandomPinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'random_pin');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'random_pin');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('random_pin.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/random_pin'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/random_pin'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/random_pin'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'random_pin');

        // Register the main class to use with the facade
        /*$this->app->singleton('random_pin', function () {
            return new RandomPin;
        });*/

        $this->app->bind(iPin::class,
        SetupPin::class);

        $this->app->bind(Pin::class, function ($app, $params) {
            $pin = $params['pin'] ?: '';
            return new Pin(new SetupPin(), $pin);
        });
    }
}
