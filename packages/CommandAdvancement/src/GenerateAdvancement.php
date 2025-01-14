<?php

namespace Packages\CommandAdvancement;

use Illuminate\Console\GeneratorCommand;

/**
 * 產生執行指令用檔案
 */
class GenerateAdvancement extends GeneratorCommand
{
    /** @var string 指令集位置 */
    const PATH = 'Console/Advancements';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advancement:make {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行 版本演化指令';

    /**
     * 調整檔案名稱
     */
    protected function getNameInput(): string
    {
        $now = now();
        $input = trim($this->argument('name'));

        return self::PATH."/{$now->format('Y_m_d')}_{$now->getTimestamp()}_{$input}";
    }

    /**
     * 獲得範例檔
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/advancement.stub';
    }
}
