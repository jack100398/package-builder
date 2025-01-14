<?php

namespace Packages\UpgradeTool;

use Illuminate\Console\Command;
use XinYin\UpgradeTool\Helper\CommonHelper;

class SendDeploySuccessfulCommand extends Command
{
    /** @var string[] 測站與前測站標誌 */
    const TESTING_SYMBOLS = [
        'test'  => 'α',
        'stage' => 'β',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy-successful';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '發送 版本更新成功訊息';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $version = $this->getGitVersion();

        $message = "<users/all> {$version} updated";

        CommonHelper::sendWebHook($message, CommonHelper::getThreadKey($version));
    }

    /**
     * 取得Git version
     *
     * @return string
     */
    public function getGitVersion(): string
    {
        $git_version = '';

        $point = shell_exec('git tag --points-at');

        $tags = collect(explode("\n", $point))->filter();

        if ($tags->isNotEmpty()) {
            $symbol = $this->getCurrentEnvSymbol();
            $git_version = $tags->first(fn ($tag) => $this->isCurrentEnvTag($tag, $symbol), '');
        }

        return $git_version;
    }

    /**
     * 目標tag 是否為當前環境所使用的
     *
     * @param string $tag
     * @param string $symbol
     *
     * @return bool
     */
    private function isCurrentEnvTag(string $tag, string $symbol): bool
    {
        if (is_null($symbol) || $symbol === '') {
            return collect(self::TESTING_SYMBOLS)->filter(fn ($env_symbol) => strpos($tag, $env_symbol))->isEmpty();
        } else {
            return ! (strpos($tag, $symbol) === false);
        }
    }

    /**
     * 取得目前環境所使用的 git tag 記號
     *
     * @return string
     */
    private function getCurrentEnvSymbol(): string
    {
        return match (env('APP_ENV')) {
            'production' => '',
            'stage' => 'β',
            'test' => 'α',
            default => ''
        };
    }
}
