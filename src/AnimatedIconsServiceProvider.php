<?php

namespace Aotr\AnimatedIcons;

use Aotr\AnimatedIcons\Console\Commands\SyncAnimatedIconsCommand;
use Aotr\AnimatedIcons\Http\Controllers\AnimatedIconsAssetController;
use Aotr\AnimatedIcons\View\Components\AnimatedIcon;
use Aotr\AnimatedIcons\View\Components\AnimatedIconsAssets;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AnimatedIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/animated-icons.php', 'animated-icons');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'animated-icons');

        Blade::component('animated-icon', AnimatedIcon::class);
        Blade::component('animated-icons-assets', AnimatedIconsAssets::class);

        Route::get('/vendor/animated-icons.js', AnimatedIconsAssetController::class)
            ->name('animated-icons.asset');

        Route::get('/vendor/animated-icons.css', [AnimatedIconsAssetController::class, 'style'])
            ->name('animated-icons.style');

        $this->publishes([
            __DIR__.'/../config/animated-icons.php' => config_path('animated-icons.php'),
        ], 'animated-icons-config');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncAnimatedIconsCommand::class]);
        }
    }
}
