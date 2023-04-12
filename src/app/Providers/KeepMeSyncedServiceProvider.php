<?php

namespace DigitalPulse\KeepMeSynced\app\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;

class KeepMeSyncedServiceProvider extends ServiceProvider
{
    public function boot(Kernel $kernel): void
    {
        $this->publishes([__DIR__ . '/../../config/keep_me_synced.php' => config_path('keep_me_synced.php')], 'config');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/keep_me_synced.php', 'keep_me_synced');
    }
}
