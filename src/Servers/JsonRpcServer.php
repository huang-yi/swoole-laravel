<?php

namespace HuangYi\Swoole\Servers;

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

        $this->createApplication();
    }

//    public function onReceive($server, $connectionId, $reactorId, $payload)
//    {
//        if ($this->overflowMaxNumOfConnections($connectionId)) {
//            // Send an error response.
//        }
//
//        $kernel = $this->getApplication()->make(Kernel::class);
//
//        $response = $kernel->handle(
//            $request = Request::parse($payload)
//        );
//
//        $response->send();
//
//        $response->terminate($request, $response);
//    }

    public function onReceive($server, $connectionId, $reactorId, $payload)
    {
        $request = Request::parse($payload);

        $response = $this->call($request);

        $this->server->send($connectionId, $response);
    }

    public function call(Request $request)
    {
        $method = $request->getMethod();
        $parameters = $request->getParams();

        return app()->call($method, $parameters);
    }

    /**
     * Create application.
     */
    protected function createApplication()
    {
        $this->application = app();
    }

    /**
     * Get application.
     */
    public function getApplication()
    {
        if (is_null($this->application)) {
            $this->application = $this->createApplication();
        }

        return $this->application;
    }
}
