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
    | Name of the Swoole process.
    |--------------------------------------------------------------------------
    |
    | You can specify a name for your Swoole process.
    |
    */

    'name' => env('SWOOLE_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Extend Swoole server configuration options.
    |--------------------------------------------------------------------------
    |
    | If the Swoole of new version add more new configuration options,
    | we can extend it by using this configuration. This option will merged
    | into \HuangYi\Swoole\Servers\Server::$serverConfigOptions
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */

    'options' => [],

    /*
    |--------------------------------------------------------------------------
    | Swoole servers configuration options.
    |--------------------------------------------------------------------------
    |
    | You can freely add configuration options according to your requirements.
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */

    'servers' => [

        /*
        |----------------------------------------------------------------------
        | Swoole http server configuration options.
        |----------------------------------------------------------------------
        */

        'http' => [

            'host' => env('SWOOLE_SERVERS_HTTP_HOST', '127.0.0.1'),

            'port' => env('SWOOLE_SERVERS_HTTP_PORT', '1215'),

            'options' => [

                'pid_file' => env('SWOOLE_SERVERS_HTTP_OPTIONS_PID_FILE', storage_path('logs/swoole-http.pid')),

                'log_file' => env('SWOOLE_SERVERS_HTTP_OPTIONS_LOG_FILE', storage_path('logs/swoole-http.log')),

            ],

        ],

        /*
        |----------------------------------------------------------------------
        | Swoole JSON-RPC server configuration options.
        |----------------------------------------------------------------------
        */

        'jsonrpc' => [

            'host' => env('SWOOLE_SERVERS_JSONRPC_HOST', '127.0.0.1'),

            'port' => env('SWOOLE_SERVERS_JSONRPC_PORT', '1216'),

            'options' => [

                'pid_file' => env('SWOOLE_SERVERS_JSONRPC_OPTIONS_PID_FILE', storage_path('logs/swoole-jsonrpc.pid')),

                'log_file' => env('SWOOLE_SERVERS_JSONRPC_OPTIONS_LOG_FILE', storage_path('logs/swoole-jsonrpc.log')),

            ],

        ],

    ],
];
