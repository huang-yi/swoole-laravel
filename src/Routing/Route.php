<?php

namespace HuangYi\Swoole\Routing;

use Illuminate\Support\Arr;
use ReflectionFunction;
use Illuminate\Support\Str;
use HuangYi\Swoole\Foundation\JsonRpc\Request;
use Illuminate\Routing\RouteDependencyResolverTrait;

class Route
{
    use RouteDependencyResolverTrait;

    /**
     * The method the route responds to.
     *
     * @var string
     */
    public $method;

    /**
     * The route action array.
     *
     * @var array
     */
    public $action;

    /**
     * The controller instance.
     *
     * @var mixed
     */
    public $controller;

    /**
     * The router instance used by the route.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The container instance used by the route.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The computed gathered middleware.
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * Route constructor.
     *
     * @param string $method
     * @param \Closure|array|null $action
     */
    public function __construct($method, $action = null)
    {
        $this->setMethod($method);
        $this->parseAction($action);
    }

    /**
     * Parse the route action into a standard array.
     *
     * @param  callable|array|null $action
     * @return array
     */
    protected function parseAction($action)
    {
        if (is_null($action)) {
            $action = ['uses' => $this->method];
        } elseif (is_callable($action) || is_string($action)) {
            $action = ['uses' => $action];
        } elseif (! isset($action['uses'])) {
            $action['uses'] = $this->method;
        }

        $this->setAction($action);
    }

    /**
     * Run route.
     *
     * @param array $params
     * @return mixed
     */
    public function run(array $params = [])
    {
        if ($this->isControllerAction()) {
            return $this->runController($params);
        }

        return $this->runCallable($params);
    }

    /**
     * Checks whether the route's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the route action and return the response.
     *
     * @param array $params
     * @return mixed
     */
    protected function runCallable($params)
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            $params, new ReflectionFunction($this->action['uses'])
        )));
    }

    /**
     * Run the route action and return the response.
     *
     * @param array $params
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function runController($params)
    {
        $controller = $this->getController();
        $method = $this->getControllerMethod();

        $parameters = $this->resolveClassMethodDependencies($params, $controller, $method);

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Get the controller instance for the route.
     *
     * @return mixed
     */
    public function getController()
    {
        $class = $this->parseControllerCallback()[0];

        if (! $this->controller) {
            $this->controller = $this->container->make($class);
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses'], '__invoke');
    }

    /**
     * Determine if the route matches given request.
     *
     * @param \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return bool
     */
    public function match(Request $request)
    {
        $requestMethod = str_replace(Router::$actionDelimiters, '@', $request->getMethod());

        return $this->getMethod() === $requestMethod;
    }

    /**
     * Get all middleware, including the ones from the controller.
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];

        return $this->computedMiddleware = array_unique(array_merge(
            $this->middleware(), $this->controllerMiddleware()
        ), SORT_REGULAR);
    }

    /**
     * Get or set the middlewares attached to the route.
     *
     * @param  array|string|null $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) Arr::get($this->action, 'middleware', []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) Arr::get($this->action, 'middleware', []), $middleware
        );

        return $this;
    }

    /**
     * Get the middleware for the route's controller.
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (! $this->isControllerAction()) {
            return [];
        }

        return $this->getControllerMiddleware(
            $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller $controller
     * @param  string $method
     * @return array
     */
    public function getControllerMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return collect($controller->getMiddleware())->reject(function ($data) use ($method) {
            return $this->methodExcludedByOptions($method, $data['options']);
        })->pluck('middleware')->all();
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string $method
     * @param  array $options
     * @return bool
     */
    protected function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the route method.
     *
     * @param string $method
     * @return string
     */
    protected function setMethod($method)
    {
        $this->method = str_replace(Router::$actionDelimiters, '@', $method);
    }

    /**
     * @return array
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param array $action
     */
    public function setAction(array $action)
    {
        $this->action = $action;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return isset($this->action['as']) ? $this->action['as'] : null;
    }

    /**
     * Add or change the route name.
     *
     * @param  string $name
     * @return $this
     */
    public function name($name)
    {
        $this->action['as'] = isset($this->action['as']) ? $this->action['as'] . $name : $name;

        return $this;
    }

    /**
     * @param \Illuminate\Container\Container $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}
