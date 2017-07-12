<?php

namespace HuangYi\Swoole\Exceptions\JsonRpc;

class InvalidRequestException extends ResponseException
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct($message = "Invalid Request", $code = -32600)
    {
        parent::__construct($message, $code);
    }
}
