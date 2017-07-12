<?php

namespace HuangYi\Swoole\Exceptions\JsonRpc;

class MethodNotFoundException extends ResponseException
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct($message = "Method not found", $code = -32601)
    {
        parent::__construct($message, $code);
    }
}
