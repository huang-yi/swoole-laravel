<?php

namespace HuangYi\Swoole\Clients;

use HuangYi\Swoole\Exceptions\JsonRpc\ConnectionException;
use Swoole\Client;

class JsonRpcClient
{
    /**
     * @var \Swoole\Client
     */
    protected $client;

    /**
     * JsonRpcClient constructor.
     *
     * @param $host
     * @param $port
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\ConnectionException
     */
    public function __construct($host, $port)
    {
        $this->initialize();
        $this->connect($host, $port);
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->client = new Client(SWOOLE_TCP | SWOOLE_KEEP);
    }

    /**
     * Connect.
     *
     * @param string $host
     * @param string $port
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\ConnectionException
     */
    protected function connect($host, $port)
    {
        if (! $this->client->connect($host, $port, -1)) {
            throw new ConnectionException('Connect failed. Host: %s:%s. Error: %s.', $host, $port, $this->client->errCode);
        }
    }

    /**
     * @param $content
     * @return string
     */
    public function send($content)
    {
        $this->client->send($content);

        return $this->client->recv();
    }

    /**
     * Close.
     */
    public function close()
    {
        $this->client->close();
    }
}
