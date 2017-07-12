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

use HuangYi\Swoole\Console\Commands\JsonRpcCommand;
use HuangYi\Swoole\Config\Repository;
use HuangYi\Swoole\Contracts\Exception\JsonRpcHandler;
use HuangYi\Swoole\Exceptions\JsonRpc\Handler;
use HuangYi\Swoole\Routing\Router;
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

        $this->registerExceptionHandler();
        $this->registerRepository();
        $this->registerJsonRpcServer();
        $this->registerRouter();

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
     * Register exception handler.
     */
    protected function registerExceptionHandler()
    {
        $this->app->singleton(JsonRpcHandler::class, Handler::class);
    }

    /**
     * Register repository.
     */
    protected function registerRepository()
    {
        $this->app->singleton(Repository::class, function ($app) {
            $repository = new Repository;

            $repository->setRepository($app['config']);

            return $repository;
        });
    }

    /**
     * Register JSON-RPC server.
     */
    protected function registerJsonRpcServer()
    {
        $this->app->singleton(JsonRpcServer::class, function ($app) {
            $jsonRpcServer = new JsonRpcServer($app, $app[Repository::class]);

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
        $host = $this->app['config']->get(sprintf('swoole.servers.%s.host', $protocol));
        $port = $this->app['config']->get(sprintf('swoole.servers.%s.port', $protocol));

        return new Server($host, $port);
    }

    /**
     * Register the router instance.
     */
    protected function registerRouter()
    {
        $this->app->singleton('swoole.router', function ($app) {
            return new Router($app);
        });

        $this->app->alias('swoole.router', Router::class);
    }
}
