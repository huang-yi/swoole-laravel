<?php

namespace HuangYi\Swoole\Exceptions\JsonRpc\Events;

class ConnectionsOverflowed
{
    /**
     * @var \Swoole\Server
     */
    public $server;

    /**
     * @var int
     */
    public $connectionId;

    /**
     * @var int
     */
    public $reactorId;

    /**
     * @var string
     */
    public $payload;

    /**
     * ConnectionsOverflowed event.
     *
     * @param \Swoole\Server $server
     * @param int $connectionId
     * @param int $reactorId
     * @param string $payload
     */
    public function __construct($server, $connectionId, $reactorId, $payload)
    {
        $this->server = $server;
        $this->connectionId = $connectionId;
        $this->reactorId = $reactorId;
        $this->payload = $payload;
    }
}
