<?php

namespace HuangYi\Swoole\Foundation\JsonRpc;

use HuangYi\Swoole\Exceptions\JsonRpc\InvalidRequestException;
use HuangYi\Swoole\Exceptions\JsonRpc\ParseErrorException;
use HuangYi\Swoole\Foundation\Request as BaseRequest;

class Request extends BaseRequest
{
    /**
     * Required members in JSON-RPC 2.0.
     *
     * @var array
     */
    protected $requiredMembers = ['jsonrpc', 'method'];

    /**
     * Optional members in JSON-RPC 2.0.
     *
     * @var array
     */
    protected $optionalMembers = ['params', 'id'];

    /**
     * JSON-RPC version.
     *
     * @var string
     */
    protected $jsonrpc;

    /**
     * Request method.
     *
     * @var string
     */
    protected $method;

    /**
     * Request parameters.
     *
     * @var mixed
     */
    protected $params;

    /**
     * Request id.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Parse JSON-RPC request from raw content.
     *
     * @param string $content
     * @return static
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\ParseErrorException
     */
    public static function parse($content)
    {
        $attributes = static::parseJson($content);

        return new static($attributes, $content);
    }

    /**
     * Request constructor.
     *
     * @param array $attributes
     * @param string $content
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\InvalidRequestException
     */
    public function __construct(array $attributes = [], $content = null)
    {
        parent::__construct($attributes, $content);

        $this->initializeJsonRpc();
    }

    /**
     * Sets the parameters for JSON-RPC request.
     *
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\InvalidRequestException
     */
    protected function initializeJsonRpc()
    {
        //  Check required members.
        foreach ($this->requiredMembers as $member) {
            if (! array_has($this->attributes, $member)) {
                throw new InvalidRequestException(
                    sprintf('Lack of required member "%s".', $member)
                );
            }

            $this->{$member} = $this->get($member);
        }

        foreach ($this->optionalMembers as $member) {
            $this->{$member} = $this->get($member);
        }
    }

    /**
     * Get parameter value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return array_get($this->params, $key, $default);
    }

    /**
     * @return string
     */
    public function getJsonrpc()
    {
        return $this->jsonrpc;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Parse json.
     *
     * @param string $string
     * @return array
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\ParseErrorException
     */
    public static function parseJson($string)
    {
        $array = json_decode($string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseErrorException('Invalid JSON was received by the server.');
        }

        return $array;
    }
}
