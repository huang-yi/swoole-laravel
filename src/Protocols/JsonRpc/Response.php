<?php

namespace HuangYi\Swoole\Protocols\JsonRpc;

class Response
{
    /**
     * Response payload.
     *
     * @var \HuangYi\Swoole\Protocols\JsonRpc\Payload
     */
    protected $payload;

    /**
     * JSON-RPC version.
     *
     * @var string
     */
    protected $version = '2.0';

    /**
     * Response constructor.
     *
     * @param \HuangYi\Swoole\Protocols\JsonRpc\Payload $payload
     */
    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @return string
     */
    public function toJson()
    {
        $this->payload->setAttribute('jsonrpc', $this->version);

        return $this->payload->toJson();
    }

    /**
     * Send response.
     *
     * @param \Swoole\Server $server
     * @param int $connectionID
     * @return bool
     */
    public function send($server, $connectionID)
    {
        return $server->send($connectionID, $this->toJson());
    }

    /**
     * Call method from payload.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->payload->$method(...$arguments);
    }
}
