<?php

namespace HuangYi\Swoole\Servers;

use HuangYi\Swoole\Foundation\JsonRpc\Kernel;
use HuangYi\Swoole\Foundation\JsonRpc\Request;
use HuangYi\Swoole\Contracts\JsonRpc\Kernel as KernelContract;
use HuangYi\Swoole\Exceptions\JsonRpc\Events\ConnectionsOverflowed;

class JsonRpcServer extends Server
{
    /**
     * The JSON-RPC kernel.
     *
     * @var \HuangYi\Swoole\Foundation\JsonRpc\Kernel
     */
    protected $kernel;

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

        $this->container->singleton(KernelContract::class, function () {
            return $this->container->make(Kernel::class);
        });
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
            $this->container['events']->fire(
                new ConnectionsOverflowed($server, $connectionId, $reactorId, $payload)
            );

            return;
        }

        $kernel = $this->container->make(KernelContract::class);

        $response = $kernel->handle(
            $request = Request::parse($payload)
        );

        $response->send($server, $connectionId);

        $kernel->terminate($request, $response);
    }
}
