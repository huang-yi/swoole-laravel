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
     * HttpServer constructor.
     *
     * @param \Swoole\Http\Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

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
        $this->setConfig();
        $this->createApplication();
    }

    /**
     * Set configurations.
     */
    protected function setConfig()
    {
        $config = app('config')->get('swoole');

        $this->server->set($config);
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
     * Bind SwooleHttpServer events' handlers.
     */
    protected function bindHandlers()
    {
        $this->setStartHandler();
        $this->setRequestHandler();
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
        $pidFile = app('config')->get('pid_file');
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
     */
    public function onRequest(Request $request, Response $response)
    {
        $this->prepareRequest($request);

        $this->obStart();
        $this->runApplication();

        $response->end($this->obEndClean());
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
     * Run Laravel application.
     */
    protected function runApplication()
    {
        $this->getApplication()->run();
    }

    /**
     * ob_start
     *
     * @return bool
     */
    protected function obStart()
    {
        return ob_start();
    }

    /**
     * ob_end_clean
     *
     * @return bool
     */
    protected function obEndClean()
    {
        return ob_end_clean();
    }

    /**
     * Start SwooleHttpServer.
     */
    protected function start()
    {
        $this->server->start();
    }

}
