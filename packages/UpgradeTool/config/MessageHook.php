<?php
return [
    'env' => [
        'develop' => [
            'symbol' => 'α',
            'name'   => '測試站'
        ],
        'staging' => [
            'symbol' => 'β',
            'name'   => '前測站台'
        ],
        'master'  => [
            'symbol' => '',
            'name'   => '正式環境'
        ],
    ],
    'url' => env('UPGRADE_HOOK_URL', ''),
    'should_thread' => env('USE_THREAD_ON_WEB_HOOK', false),
];
