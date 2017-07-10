<?php

namespace HuangYi\Swoole\Protocols\JsonRpc;

use ArrayAccess;
use HuangYi\Swoole\Exceptions\JsonRpc\InvalidJsonException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

class Payload implements ArrayAccess, Arrayable, Jsonable
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Original json string.
     *
     * @var string
     */
    protected $json;

    /**
     * Payload constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get attribute value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * Set attribute.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Remove attribute.
     *
     * @param string $key
     */
    public function removeAttribute($key)
    {
        Arr::forget($this->attributes, $key);
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Merge attributes.
     *
     * @param array $attributes
     */
    public function mergeAttributes(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * @param array $attributes
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Payload
     */
    public static function make(array $attributes = [])
    {
        return new static($attributes);
    }

    /**
     * Parse payload.
     *
     * @param string $payload
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Payload
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\InvalidJsonException
     */
    public static function parse($payload)
    {
        $attributes = self::decode($payload);

        return static::make($attributes);
    }

    /**
     * Parse json.
     *
     * @param $payload
     * @return array
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\InvalidJsonException
     */
    public static function decode($payload)
    {
        $array = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(sprintf('"%s" is not a json', $payload));
        }

        return $array;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->getAttributes());
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Remove the value at the given offset.
     *
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->removeAttribute($offset);
    }

    /**
     * Get all of the input and files for the request.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->attributes, $options);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
