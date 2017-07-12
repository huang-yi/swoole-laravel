<?php

namespace HuangYi\Swoole\Servers;

use HuangYi\Swoole\Foundation\JsonRpc\Kernel;
use HuangYi\Swoole\Foundation\JsonRpc\Request;

class JsonRpcServer extends Server
{
    /**
     * @var
     */
    protected $application;

    /**
     * Set the protocol name.
     *
     * @return string
     */
    public function protocol()
    {
        return 'jsonrpc';
    }

    /**
     * Create application.
     */
    public function onWorkerStart()
    {
        parent::onWorkerStart();
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

        $kernel = $this->container->make(Kernel::class);

        $response = $kernel->handle(
            $request = Request::parse($payload)
        );

        $response->send($server, $connectionId);

        $response->terminate($request, $response);
    }
}
