<?php

namespace HuangYi\Swoole\Exceptions\JsonRpc;

class InternalErrorException extends ResponseException
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array|null $data
     */
    public function __construct($message = "Internal error", $code = -32603, array $data = null)
    {
        parent::__construct($message, $code, $data);
    }
}
