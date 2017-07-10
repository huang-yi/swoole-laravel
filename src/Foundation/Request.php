<?php

namespace HuangYi\Swoole\Foundation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidatesWhenResolvedTrait;

class Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Attributes.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Raw content.
     *
     * @var string
     */
    protected $content;

    /**
     * Request constructor.
     *
     * @param array $attributes
     * @param string $content
     */
    public function __construct(array $attributes = [], $content = null)
    {
        $this->initialize($attributes, $content);
    }

    /**
     * Sets the parameters for this request.
     *
     * @param array $attributes
     * @param string $content
     */
    protected function initialize(array $attributes = [], $content = null)
    {
        $this->attributes = $attributes;
        $this->content = $content;
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function all()
    {
        return $this->getAttributes();
    }

    /**
     * Get attribute.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->attributes, $key, $default);
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get raw content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container implementation.
     *
     * @param  \Illuminate\Contracts\Container\Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator()
    {
        $factory = $this->container->make(ValidationFactory::class);
        $validator = $this->createDefaultValidator($factory);

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
            $this->validationData(), $this->container->call([$this, 'rules']),
            $this->messages(), $this->attributes()
        );
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    protected function validationData()
    {
        return $this->all();
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
}
