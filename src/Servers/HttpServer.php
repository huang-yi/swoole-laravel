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
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    protected $options = [
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

        foreach ( $options as $option ) {
            $envKey = 'SWOOLE_SERVER_' . strtoupper($option);
            $envValue = env($envKey);

            if ( ! is_null($envValue) ) {
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

        return array_merge($this->options, $extendOptions);
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
     * Set SwooleHttpServer onWorkerStartEvent handler.
     */
    protected function setWorkerStartHandler()
    {
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
    }

    /**
     * SwooleHttpServer onWorkerStartEvent handler.
     *
     * @param \Swoole\Http\Server $server
     * @param int $workerID
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    public function onWorkerStart(Server $server, $workerID)
    {
        $this->createApplication();
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
        $pidFile = $this->getPIDFile();
        $pid = $this->server->master_pid;

        file_put_contents($pidFile, $pid);
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
     */
    public function onRequest(Request $request, Response $response)
    {
        $this->prepareRequest($request);

        $applicationResponse = $this->runApplication();

        if ( $applicationResponse instanceof SymfonyResponse ) {
            $this->response($response, $applicationResponse);
        } else {
            $response->end((string) $applicationResponse);
        }
    }

    /**
     * Swoole response.
     *
     * @param \Swoole\Http\Response $response
     * @param \Symfony\Component\HttpFoundation\Response $symfonyResponse
     * @throws \InvalidArgumentException
     */
    protected function response(Response $response, SymfonyResponse $symfonyResponse)
    {
        // headers
        foreach ($symfonyResponse->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        // cookies
        foreach ($symfonyResponse->headers->getCookies() as $cookie) {
            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        // status
        $response->status($symfonyResponse->getStatusCode());

        // stream
        if ( $symfonyResponse instanceof StreamedResponse ) {
            //  No processing currently.
            $response->end();
        }
        // file
        elseif ( $symfonyResponse instanceof BinaryFileResponse ) {
            $response->sendfile($symfonyResponse->getFile()->getPathname());
        }
        // text
        else {
            $response->end($symfonyResponse->getContent());
        }
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
     * Prepare request for Laravel application.
     *
     * @param \Swoole\Http\Request $request
     */
    protected function prepareRequest(Request $request)
    {
        $_GET = isset($request->get) ? $request->get : [];
        $_POST = isset($request->post) ? $request->post : [];
        $_FILES = isset($request->files) ? $request->files : [];
        $_COOKIE = isset($request->cookie) ? $request->cookie : [];
        $_SERVER = isset($request->server) ? $this->formatServerParameter($request->server, $request->header) : [];
    }

    /**
     * Transform swoole's $request->server to php's $_SERVER.
     *
     * @param array $server
     * @param array $header
     * @return array
     */
    protected function formatServerParameter($server, $header)
    {
        $phpServer = [];

        foreach ( $server as $key => $value ) {
            $phpKey = strtoupper($key);
            $phpServer[$phpKey] = $value;
        }

        foreach ( $header as $key => $value ) {
            $phpKey = str_replace('-', '_', $key);
            $phpKey = 'HTTP_' . strtoupper($phpKey);
            $phpServer[$phpKey] = $value;
        }

        return $phpServer;
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
        if ( $this->application instanceof Application ) {
            return $this->application;
        }

        return $this->createApplication();
    }

    /**
     * Run Laravel application.
     *
     * @return mixed
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    protected function runApplication()
    {
        return $this->getApplication()->run();
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

}
