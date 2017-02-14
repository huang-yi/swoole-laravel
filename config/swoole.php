<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Number of worker
    |--------------------------------------------------------------------------
    |
    | Set the number of worker. The default is null. It equals to your CPU
    | cores number. Official advice is set to the number of CPU cores 1-4
    | times.
    |
    */

    'worker_num' => env('SWOOLE_WORKER_NUM', null),

    /*
    |--------------------------------------------------------------------------
    | If run the command in daemonize mode
    |--------------------------------------------------------------------------
    |
    | The default is true.
    |
    */

    'daemonize' => true,

    /*
    |--------------------------------------------------------------------------
    | Log file path
    |--------------------------------------------------------------------------
    |
    | When setting the daemonize is true, all log information will be written
    | into this file.
    |
    */

    'log_file' => storage_path('logs/swoole.log'),

    /*
    |--------------------------------------------------------------------------
    | Pid file path
    |--------------------------------------------------------------------------
    |
    */

    'pid_file' => storage_path('logs/swoole.pid'),

    /*
    |--------------------------------------------------------------------------
    | Max coroutine number
    |--------------------------------------------------------------------------
    |
    | It is effective when enabled swoole's coroutine. Default value is 3000.
    |
    */

    'max_coro_num' => env('SWOOLE_MAX_CORO_NUM', 3000),

];
