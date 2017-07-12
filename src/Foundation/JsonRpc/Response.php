<?php

namespace HuangYi\Swoole\Foundation\JsonRpc;

use HuangYi\Swoole\Foundation\Response as BaseResponse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Response extends BaseResponse implements Arrayable, Jsonable
{
    /**
     * @var string
     */
    protected $jsonrpc = '2.0';

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var \HuangYi\Swoole\Foundation\JsonRpc\ErrorBag
     */
    protected $error;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * Codes translation table.
     *
     * The list of codes is complete according to the
     * {@link http://www.jsonrpc.org/specification#error_object JSON-RPC 2.0 Specification for error object}
     * (last updated 2013-01-04).
     *
     * @var array
     */
    public static $codeTexts = [
        -32700 => 'Parse error',
        -32600 => 'Invalid Request',
        -32601 => 'Method not found',
        -32602 => 'Invalid params',
        -32603 => 'Internal error',
        -32000 => 'Server error',
    ];

    /**
     * @param mixed $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @param \HuangYi\Swoole\Foundation\JsonRpc\ErrorBag $error
     * @return $this
     */
    public function setError(ErrorBag $error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getJsonrpc()
    {
        return $this->jsonrpc;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \HuangYi\Swoole\Foundation\JsonRpc\ErrorBag
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->toJson();
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->getError() instanceof ErrorBag;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [
            'jsonrpc' => $this->getJsonrpc(),
            'id' => $this->getId(),
        ];

        if ($this->hasError()) {
            $array['error'] = $this->getError()->toArray();
        } else {
            $array['result'] = $this->getResult();
        }

        return $array;
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * @param \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Response
     */
    public function prepare(Request $request)
    {
        $this->setId($request->getId());

        return $this;
    }
}
