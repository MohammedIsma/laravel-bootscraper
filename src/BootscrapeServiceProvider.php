<?php

namespace Misma\Bootscraper;

use Illuminate\Support\ServiceProvider;

class BootscrapeServiceProvider extends ServiceProvider
{

    protected $commands = [
        'Misma\Bootscraper\Commands\GenerateLayout',
    ];
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'bootscraper');
        
        $this->publishes([
            __DIR__.'/config/bootscraper.php' => config_path('bootscraper.php'),
            __DIR__.'/views' => base_path('resources/views'),
            ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // include __DIR__.'/routes.php';
         $this->commands($this->commands);
    }
}
