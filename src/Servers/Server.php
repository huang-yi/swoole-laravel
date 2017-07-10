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

use HuangYi\Swoole\Config\Repository;
use Illuminate\Contracts\Container\Container;

abstract class Server
{
    /**
     * Swoole server.
     *
     * @var \Swoole\Server
     */
    protected $server;

    /**
     * Connections pool.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Max connections count.
     *
     * @var int
     */
    protected $maxNumOfConnections = 10240;

    /**
     * Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var \HuangYi\Swoole\Config\Repository
     */
    protected $config;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start', 'shutDown', 'workerStart', 'workerStop', 'timer', 'connect',
        'receive', 'packet', 'close', 'bufferFull', 'bufferEmpty', 'task',
        'finish', 'pipeMessage', 'workerError', 'managerStart', 'managerStop',
    ];

    /**
     * Server constructor.
     */
    public function __construct()
    {
        $this->setProcessName('manager process');
    }

    /**
     * Set the protocol name.
     *
     * @return string
     */
    abstract public function protocol();

    /**
     * Set container.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \HuangYi\Swoole\Config\Repository $config
     */
    public function setConfig(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Set swoole server.
     *
     * @param \Swoole\Server $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * Run the server.
     */
    public function run()
    {
        $this->initialize();
        $this->start();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->initServerConfig();
        $this->initServerListeners();
    }

    /**
     * Init server config
     */
    public function initServerConfig()
    {
        $protocol = $this->protocol();
        $options = $this->config->getServerOptions($protocol);

        $this->server->set($options);
    }

    /**
     * Init server events' listeners.
     */
    public function initServerListeners()
    {
        foreach ($this->events as $event) {
            $event = ucfirst($event);
            $listener = 'on' . $event;

            if (method_exists($this, $listener)) {
                $this->server->on($event, [$this, $listener]);
            }
        }
    }

    /**
     * Set onStart listener.
     */
    public function onStart()
    {
        $this->setProcessName('master process');

        $pidFile = $this->getPidFile();
        $pid = $this->server->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Set onWorkerStart listener.
     */
    public function onWorkerStart()
    {
        $this->clearCache();

        $this->setProcessName('worker process');
    }

    /**
     * Set onReceive listener.
     *
     * @param \Swoole\Server $server
     * @param int $connectionId
     * @param int $reactorId
     * @param string $payload
     */
    public function onReceive($server, $connectionId, $reactorId, $payload)
    {
        if ($this->overflowMaxNumOfConnections($connectionId)) {
            // Send an error response.
        }

        // Then, do what you want.
    }

    /**
     * Set onShutdown listener.
     *
     * @param \Swoole\Server $server
     */
    public function onShutdown($server)
    {
        unlink($this->getPidFile());
    }

    /**
     * Start the swoole server.
     */
    protected function start()
    {
        $this->server->start();
    }

    /**
     * Check if specified connection has been overflowed max number of connections.
     *
     * @param int $connectionId
     * @return bool
     */
    public function overflowMaxNumOfConnections($connectionId)
    {
        if (array_key_exists($connectionId, $this->connections)) {
            return false;
        }

        if (! $this->isBusy($this->maxNumOfConnections - 1)) {
            return false;
        }

        return true;
    }

    /**
     * Check if server is busy.
     *
     * @param int $maxNumOfConnections
     * @return bool
     */
    public function isBusy($maxNumOfConnections = null)
    {
        $maxNumOfConnections = is_null($maxNumOfConnections) ? $this->maxNumOfConnections : $maxNumOfConnections;

        return count($this->connections) > $maxNumOfConnections;
    }

    /**
     * Get name.
     *
     * @return string
     */
    protected function getSwooleName()
    {
        return $this->config->get('swoole.name');
    }

    /**
     * Get pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        $protocol = $this->protocol();

        return $this->config->getServerPidFile($protocol);
    }

    /**
     * @param $process
     */
    protected function setProcessName($process)
    {
        $serverName = sprintf('swoole_%s_server', $this->protocol());
        $swooleName = $this->getSwooleName();
        $swooleName = empty($swooleName) ? '' : 'for ' . $swooleName;

        $name = sprintf('%s: %s %s', $serverName, $process, $swooleName);

        swoole_set_process_name($name);
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
}
