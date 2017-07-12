<?php

namespace HuangYi\Swoole\Exceptions\JsonRpc;

class InvalidParamsException extends ResponseException
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array|null $data
     */
    public function __construct($message = "Invalid params", $code = -32602, array $data = null)
    {
        parent::__construct($message, $code, $data);
    }
}
