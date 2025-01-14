<?php

namespace Packages\CommandAdvancement;

/**
 * 指令執行物件抽像類
 */
abstract class Advancement
{
    /**
     * 執行指令
     *
     * @return void
     */
    abstract public function run(): void;
}
