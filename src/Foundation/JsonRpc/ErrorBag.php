<?php

namespace HuangYi\Swoole\Foundation\JsonRpc;

use Illuminate\Contracts\Support\Arrayable;

class ErrorBag implements Arrayable
{
    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * ErrorBag constructor.
     *
     * @param int $code
     * @param string $message
     * @param mixed $data
     */
    public function __construct($code, $message, $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $error = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];

        $data = $this->getData();

        if (! is_null($data)) {
            $error['data'] = $data;
        }

        return $error;
    }
}
