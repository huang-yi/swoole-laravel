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
        $this->setRequestHandler();
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
        $this->server->set([

        ]);
    }

    /**
     * Create application.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     */
    protected function createApplication()
    {
        return $this->application = Application::make();
    }

    /**
     * Get Application.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     */
    protected function getApplication()
    {
        if ( $this->application instanceof Application ) {
            return $this->application;
        }

        return $this->createApplication();
    }

    /**
     * Set SwooleHttpServer onRequestEvent handler.
     */
    protected function setRequestHandler()
    {
        $this->server->on('Request', [$this, 'onRequest']);
    }

    /**
     * SwooleHttpServer onRequest callback.
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    protected function onRequest(Request $request, Response $response)
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
     * @return \Illuminate\Http\Request
     */
    protected function prepareRequest(request $request)
    {

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
