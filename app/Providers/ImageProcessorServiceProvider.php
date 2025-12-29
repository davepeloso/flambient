<?php

namespace App\Providers;

use App\Services\ImageProcessor\Generators\FlambientGenerator;
use App\Services\ImageProcessor\ScriptGeneratorRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Image Processor Service Provider.
 *
 * Registers all available script generators and makes the
 * registry available for dependency injection.
 */
class ImageProcessorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the generator registry as a singleton
        $this->app->singleton(ScriptGeneratorRegistry::class, function ($app) {
            $registry = new ScriptGeneratorRegistry();

            // Register all available generators
            $registry->register(new FlambientGenerator());

            // Future generators will be registered here:
            // $registry->register(new HDRMergeGenerator());
            // $registry->register(new FocusStackGenerator());
            // $registry->register(new DMECGenerator());

            return $registry;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
