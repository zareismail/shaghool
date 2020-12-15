<?php

namespace Zareismail\Shaghool;
 
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;  
use Laravel\Nova\Nova as LaravelNova; 

class ShaghoolServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Models\ShaghoolResource::class => Policies\MeasurableResource::class,
        Models\ShaghoolPerCapita::class => Policies\PerCapita::class,
        Models\ShaghoolReport::class => Policies\ConsumptionReport::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');  
        LaravelNova::serving([$this, 'servingNova']);
        $this->registerPolicies();
    }

    /**
     * Register any Nova services.
     *
     * @return void
     */
    public function servingNova()
    { 
        LaravelNova::resources([
            Nova\MeasurableResource::class,
            Nova\PerCapita::class,
            Nova\ConsumptionReport::class,
        ]);
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
