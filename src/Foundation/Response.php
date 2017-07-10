<?php

namespace HuangYi\Swoole\Foundation;

class Response
{
    /**
     * @var string
     */
    protected $content;

    /**
     * Response constructor.
     *
     * @param string $content
     */
    public function __construct($content = '')
    {
        $this->setContent($content);
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = (string) $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Send response to client.
     *
     * @param \Swoole\Server $server
     * @param $connectionID
     * @return bool
     */
    public function send($server, $connectionID)
    {
        return $server->send($connectionID, $this->__toString());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getContent();
    }
}
