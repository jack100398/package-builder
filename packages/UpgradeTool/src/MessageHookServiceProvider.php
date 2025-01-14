<?php

namespace Packages\UpgradeTool;

use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Support\ServiceProvider;

class MessageHookServiceProvider extends ServiceProvider
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
        $this->registerDefaultSettingConfig();

        $this->setPublishFile();

        $this->commands(SendVersionMessageCommand::class);
        $this->commands(SendDeploySuccessfulCommand::class);
    }

    /**
     * 註冊預設設定檔
     *
     * @return void
     */
    protected function registerDefaultSettingConfig(): void
    {
        $source = realpath($raw = __DIR__.'/../config/MessageHook.php') ?: $raw;

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('MessageHook.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('MessageHook');
        }

        $this->mergeConfigFrom($source, 'MessageHook');
    }

    /**
     * 發布設定檔設定
     *
     * @return void
     */
    protected function setPublishFile(): void
    {
        $this->publishes([__DIR__ . '/../config/MessageHook.php' => config_path('custommessagehook.php'),], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../config/MessageHook.php', 'custommessagehook');
    }
}
