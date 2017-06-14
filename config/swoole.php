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

    'name' => env('SWOOLE_NAME'),

    /*
    |--------------------------------------------------------------------------
    | The IP address of the Swoole server listening.
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
    | The port of the Swoole server listening.
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
    | If the Swoole of new version add more new configuration options,
    | we can extend it by using this configuration. This option will merged
    | into \HuangYi\Swoole\Servers\HttpServer::$options
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

    /*
    |--------------------------------------------------------------------------
    | Before swoole http server start handler.
    |--------------------------------------------------------------------------
    |
    | You can do something before starting a swoole http server. By default, we
    | will clear the APC or OPcache. This option only supports closure.
    |
    */

    'before_start' => null,

];
