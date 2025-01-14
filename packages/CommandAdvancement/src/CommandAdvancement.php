<?php

namespace Packages\CommandAdvancement;

use Illuminate\Database\Eloquent\Model;

/**
 * 指令執行紀錄ORM
 */
class CommandAdvancement extends Model
{
    protected $table = 'command_advancements';

    protected $fillable = [
        'file'
    ];
}
