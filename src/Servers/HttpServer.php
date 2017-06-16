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
use Illuminate\Http\Request as IlluminateRequest;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
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
        $this->setProcessName('manager process');
        $this->createServer();
        $this->createApplication();
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
        $this->setProcessName('worker process');
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
        $requestStart = microtime(true);

        $illuminateRequest = $this->prepareRequest($request, $requestStart);

        $applicationResponse = $this->runApplication($illuminateRequest);

        if ($applicationResponse instanceof SymfonyResponse) {
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
        if ($symfonyResponse instanceof StreamedResponse) {
            //  No processing currently.
            $response->end();
        } // file
        elseif ($symfonyResponse instanceof BinaryFileResponse) {
            $response->sendfile($symfonyResponse->getFile()->getPathname());
        } // text
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
     * @param float $requestStart
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    protected function prepareRequest(Request $request, $requestStart)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $files = isset($request->files) ? $request->files : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $server = isset($request->server) ? $this->formatServerParameter($request->server, $request->header) : [];

        // set request start time into $_SERVER array
        $server['REQUEST_START'] = $requestStart;

        return $this->createIlluminateRequest($get, $post, $cookie, $files, $server);
    }

    /**
     * Create an illuminate type request.
     * Copy from \Symfony\Component\HttpFoundation\Request::capture().
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    protected function createIlluminateRequest($get, $post, $cookie, $files, $server)
    {
        IlluminateRequest::enableHttpMethodParameterOverride();

        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
                $server['CONTENT_LENGTH'] = $server['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
                $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
            }
        }

        $request = new IlluminateRequest($get, $post, [], $cookie, $files, $server);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return IlluminateRequest::createFromBase($request);
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

        foreach ($server as $key => $value) {
            $phpKey = strtoupper($key);
            $phpServer[$phpKey] = $value;
        }

        foreach ($header as $key => $value) {
            $phpKey = strtoupper(str_replace('-', '_', $key));

            if (! in_array($phpKey, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $phpKey = 'HTTP_' . $phpKey;
            }

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
        if ($this->application instanceof Application) {
            return $this->application;
        }

        return $this->createApplication();
    }

    /**
     * Run Laravel application.
     *
     * @param \Illuminate\Http\Request $request
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
