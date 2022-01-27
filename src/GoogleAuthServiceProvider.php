<?php

namespace Glamstack\GoogleAuth;

use Illuminate\Support\ServiceProvider;

class GoogleAuthServiceProvider extends ServiceProvider
{
    // use ServiceBindings;

    public function boot() : void
    {
        $this->bootRoutes();
    }

    public function register()
    {
        $this->registerConfig();
        $this->registerServices();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        //$this->loadRoutesFrom(__DIR__.'/Routes/console.php');
    }

    protected function registerConfig() : void
    {

        //
        // Merge config file into application config
        //
        // This allows users to override any module configuration values with
        // their own values in the application config file.
        //
        $this->mergeConfigFrom(
            __DIR__.'/Config/glamstack-google-config.php',
            'glamstack-google-config'
        );

        if (! $this->app->runningInConsole()) {
            return;
       }

        //
        // Publish config file to application
        //
        // Once the `php artisan vendor::publish` command is run, you can use
        // the configuration file values `$value = config('glamstack-gitlab.option');`
        //
        $this->publishes(
            [
                __DIR__.'/Config/glamstack-google-config.php' => config_path(
                    'glamstack-google-config.php'
                ),
            ], 
            'glamstack-google-config');
    }

    /**
     * Register package services in the container.
     *
     * @return void
     */
    protected function registerServices()
    {
        if (! property_exists($this, 'serviceBindings')) {
            return;
        }

        foreach ($this->serviceBindings as $key => $value) {
            is_numeric($key)
                    ? $this->app->singleton($value)
                    : $this->app->singleton($key, $value);
        }
    }
}
