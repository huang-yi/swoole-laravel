<?php

namespace HuangYi\Swoole\Protocols\JsonRpc;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Validation\ValidationException;

class Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * @var \HuangYi\Swoole\Protocols\JsonRpc\Payload
     */
    protected $payload;

    /**
     * Request constructor.
     *
     * @param \HuangYi\Swoole\Protocols\JsonRpc\Payload $payload
     */
    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get input value.
     *
     * @param string $key
     * @return mixed
     */
    public function input($key)
    {
        return $this->payload->getAttribute($key);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->payload->getAttributes();
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param array|mixed $keys
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];

        $input = $this->all();

        foreach ($keys as $key) {
            Arr::set($results, $key, data_get($input, $key));
        }

        return $results;
    }

    /**
     * Intersect an array of items with the input data.
     *
     * @param  array|mixed $keys
     * @return array
     */
    public function intersect($keys)
    {
        return array_filter($this->only(is_array($keys) ? $keys : func_get_args()));
    }

    /**
     * Merge attributes.
     *
     * @param array $attributes
     */
    public function merge(array $attributes)
    {
        $this->payload->mergeAttributes($attributes);
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $factory = app(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            $validator = app()->call([$this, 'validator'], compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        return $validator;
    }

    /**
     * Create the default validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Factory $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->all(), app()->call([$this, 'rules']),
            $this->messages(), $this->attributes()
        );
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, $this->response(
            $this->formatErrors($validator)
        ));
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array $errors
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public function response(array $errors)
    {
        return ResponseFactory::errorInvalidParams($errors);
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        return $validator->getMessageBag()->toArray();
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Parse from payload.
     *
     * @param string $payload
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Request
     * @throws \HuangYi\Swoole\Exceptions\JsonRpc\InvalidJsonException
     */
    public static function parse($payload)
    {
        $payload = Payload::parse($payload);

        return new static($payload);
    }

    /**
     * Call method from payload.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->payload->$method(...$arguments);
    }
}
