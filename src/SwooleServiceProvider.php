<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Swoole;

use HuangYi\Swoole\Commands\HttpServerCommand;
use HuangYi\Swoole\Commands\JsonRpcCommand;
use HuangYi\Swoole\Servers\JsonRpcServer;
use Illuminate\Support\ServiceProvider;
use Swoole\Server;

class SwooleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/swoole.php', 'swoole');

        $this->commands([JsonRpcCommand::class]);
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/swoole.php' => config_path('swoole.php')
            ], 'config');
        }
    }

    /**
     * Register JSON-RPC server.
     */
    protected function registerJsonRpcServer()
    {
        $this->app->singleton(JsonRpcServer::class, function ($app) {
            $jsonRpcServer = new JsonRpcServer($app, $app['config']);

            $jsonRpcServer->setServer($this->createSwooleServer('jsonrpc'));

            return $jsonRpcServer;
        });
    }

    /**
     * Create swoole server.
     *
     * @param string $protocol
     * @return \Swoole\Server
     */
    protected function createSwooleServer($protocol)
    {
        $host = app('config')->get(sprintf('swoole.servers.%s.host', $protocol));
        $port = app('config')->get(sprintf('swoole.servers.%s.port', $protocol));

        return new Server($host, $port);
    }
}
