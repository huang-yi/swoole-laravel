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
    | An IP address of the Swoole server listening.
    |--------------------------------------------------------------------------
    |
    | Listening on 127.0.0.1(localhost) by default.
    | You can also use 0.0.0.0 to listen on all IP addresses,
    | or specify a LAN/WAN IP address.
    |
    | @see https://wiki.swoole.com/wiki/page/14.html
    |
    */

    'host' => env('SWOOLE_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | A port of the Swoole server listening.
    |--------------------------------------------------------------------------
    |
    | Listening on 1215 by default.
    |
    | @see https://wiki.swoole.com/wiki/page/14.html
    |
    */

    'port' => env('SWOOLE_PORT', '1215'),

    /*
    |--------------------------------------------------------------------------
    | Extend Swoole server configuration options.
    |--------------------------------------------------------------------------
    |
    | If the Swoole of new version has more new configuration options,
    | we can extend it by using this configuration.
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */

    'options' => [],

    /*
    |--------------------------------------------------------------------------
    | Swoole server configuration options.
    |--------------------------------------------------------------------------
    |
    | You can freely add configuration options according to your requirements.
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */

    'server' => [

        'daemonize' => env('SWOOLE_SERVER_DAEMONIZE', 1),

        'log_file' => env('SWOOLE_SERVER_LOG_FILE', storage_path('logs/swoole.log')),

        'pid_file' => env('SWOOLE_SERVER_PID_FILE', storage_path('logs/swoole.pid')),

    ],

];
