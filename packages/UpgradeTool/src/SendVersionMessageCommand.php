<?php

namespace Packages\UpgradeTool;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use XinYin\UpgradeTool\Helper\CommonHelper;

class SendVersionMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade {target : 指定站台 預設為 (develop/cron/staging/master)}';

    protected array $settings = [];

    protected array $current_setting = [];

    protected string $target;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '發送 git tag commit message hook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->verify()) {
            return false;
        }

        $this->action();
    }

    protected function action(): void
    {
        (new MessageHookController($this->target))->updateEnv();
    }

    protected function verify(): bool
    {
        $this->settings = CommonHelper::getEnvSettings();

        $this->target = $this->argument('target');

        if (! Arr::has($this->settings, $this->target)) {
            $setting_tip = collect($this->settings)->keys()->join('/');
            $this->warn("請夾帶正確的站台參數 --target= ({$setting_tip})");

            return false;
        }

        $this->current_setting = Arr::get($this->settings, $this->target);

        if (! Arr::has($this->current_setting, 'symbol') || ! Arr::has($this->current_setting, 'name')) {
            $this->warn("請確認設定檔內容,各項皆需包含 symbol 與 name");

            return false;
        }

        return true;
    }
}
