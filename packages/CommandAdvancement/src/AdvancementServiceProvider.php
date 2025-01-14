<?php

namespace Packages\CommandAdvancement;

use Illuminate\Support\ServiceProvider;

class AdvancementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations/2024_10_09_034649_create_command_advancement_table.php');
        $this->commands(RunAdvancement::class);
        $this->commands(GenerateAdvancement::class);
    }
}
