<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Swoole\Servers;

use HuangYi\Swoole\Foundation\Application;
use HuangYi\Swoole\Foundation\Request as IlluminateRequest;
use HuangYi\Swoole\Foundation\ResponseFactory;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class HttpServer
{
    /**
     * @var \Swoole\Http\Server
     */
    protected $server;

    /**
     * @var \HuangYi\Swoole\Foundation\Application
     */
    protected $application;

    /**
     * Swoole Server allowed configuration options.
     *
     * @var array
     */
    public static $options = [
        'reactor_num', 'worker_num', 'max_request', 'max_conn',
        'task_worker_num', 'task_ipc_mode', 'task_max_request', 'task_tmpdir',
        'dispatch_mode', 'message_queue_key', 'daemonize', 'backlog',
        'log_file', 'log_level', 'heartbeat_check_interval',
        'heartbeat_idle_time', 'open_eof_check', 'open_eof_split',
        'package_eof', 'open_length_check', 'package_length_type',
        'package_length_func', 'package_max_length', 'open_cpu_affinity',
        'cpu_affinity_ignore', 'open_tcp_nodelay', 'tcp_defer_accept',
        'ssl_cert_file', 'ssl_method', 'user', 'group', 'chroot', 'pid_file',
        'pipe_buffer_size', 'buffer_output_size', 'socket_buffer_size',
        'enable_unsafe_event', 'discard_timeout_request', 'enable_reuse_port',
        'ssl_ciphers', 'enable_delay_receive', 'open_http_protocol',
        'open_http2_protocol', 'open_websocket_protocol'
    ];

    /**
     * Run the HttpServer.
     */
    public function run()
    {
        $this->init();
        $this->bindHandlers();
        $this->start();
    }

    /**
     * Initialize.
     */
    protected function init()
    {
        define('RUN_IN_SWOOLE', true);

        $this->setProcessName('manager process');
        $this->createServer();
        $this->setConfig();
    }

    /**
     * Create SwooleHttpServer.
     */
    protected function createServer()
    {
        $host = app('config')->get('swoole.host');
        $port = app('config')->get('swoole.port');

        $this->server = new Server($host, $port);
    }

    /**
     * Set configurations.
     */
    protected function setConfig()
    {
        $config = app('config')->get('swoole.server');

        $envConfig = [];
        $options = $this->getOptions();

        foreach ($options as $option) {
            $envKey = 'SWOOLE_SERVER_' . strtoupper($option);
            $envValue = env($envKey);

            if (! is_null($envValue)) {
                $envConfig[$option] = $envValue;
            }
        }

        $config = array_merge($config, $envConfig);

        $this->server->set($config);
    }

    /**
     * Get Swoole Server allowed configuration options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $extendOptions = (array) app('config')->get('swoole.options');

        $envOptions = env('SWOOLE_OPTIONS');

        if (empty($envOptions)) {
            $envOptions = [];
        } else {
            $envOptions = explode(',', $envOptions);
        }

        $extendOptions = array_merge($extendOptions, $envOptions);

        return array_merge(self::$options, $extendOptions);
    }

    /**
     * Bind SwooleHttpServer events' handlers.
     */
    protected function bindHandlers()
    {
        $this->setStartHandler();
        $this->setWorkerStartHandler();
        $this->setRequestHandler();
        $this->setShutdownHandler();
    }

    /**
     * Set SwooleHttpServer onStartEvent handler.
     */
    protected function setStartHandler()
    {
        $this->server->on('Start', [$this, 'onStart']);
    }

    /**
     * SwooleHttpServer onStartEvent handler.
     */
    public function onStart()
    {
        $this->setProcessName('master process');

        $pidFile = $this->getPIDFile();
        $pid = $this->server->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Set SwooleHttpServer onWorkerStartEvent handler.
     */
    protected function setWorkerStartHandler()
    {
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
    }

    /**
     * SwooleHttpServer onWorkerStartEvent handler.
     */
    public function onWorkerStart()
    {
        $this->clearCache();

        $this->setProcessName('worker process');

        $this->createApplication();
    }

    /**
     * Set SwooleHttpServer onRequestEvent handler.
     */
    protected function setRequestHandler()
    {
        $this->server->on('Request', [$this, 'onRequest']);
    }

    /**
     * SwooleHttpServer onRequestEvent handler.
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function onRequest(Request $request, Response $response)
    {
        $illuminateRequest = IlluminateRequest::swooleCapture($request);
        $illuminateResponse = $this->runApplication($illuminateRequest);
        $swooleResponse = ResponseFactory::createFromIlluminate($response, $illuminateResponse);

        $swooleResponse->send();
    }

    /**
     * Set SwooleHttpServer onShutdownEvent handler.
     */
    protected function setShutdownHandler()
    {
        $this->server->on('Shutdown', [$this, 'onShutdown']);
    }

    /**
     * SwooleHttpServer onShutdownEvent handler.
     *
     * @param \Swoole\Http\Server $server
     */
    public function onShutdown(Server $server)
    {
        unlink($this->getPIDFile());
    }

    /**
     * Create application.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    protected function createApplication()
    {
        return $this->application = Application::make();
    }

    /**
     * Get Application.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    protected function getApplication()
    {
        if ($this->application instanceof Application) {
            return $this->application;
        }

        return $this->createApplication();
    }

    /**
     * Run Laravel application.
     *
     * @param \HuangYi\Swoole\Foundation\Request $request
     * @return mixed
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    protected function runApplication($request)
    {
        return $this->getApplication()->run($request);
    }

    /**
     * Get pid file path.
     *
     * @return string
     */
    protected function getPIDFile()
    {
        return app('config')->get('swoole.server.pid_file');
    }

    /**
     * Start SwooleHttpServer.
     */
    protected function start()
    {
        $this->server->start();
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @param string $type
     */
    protected function setProcessName($type)
    {
        $name = $this->getConfigName();
        $process = sprintf('swoole_http_server: %s%s', $type, $name);

        swoole_set_process_name($process);
    }

    /**
     * @return string
     */
    protected function getConfigName()
    {
        $name = app('config')->get('swoole.name');

        return $name ? ' for ' . $name : '';
    }

}
