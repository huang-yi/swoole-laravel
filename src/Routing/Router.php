<?php

namespace HuangYi\Swoole\Routing;

use Closure;
use HuangYi\Swoole\Foundation\JsonRpc\Request;
use HuangYi\Swoole\Foundation\JsonRpc\Response;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\SortedMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Router
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
     *
     * @var \HuangYi\Swoole\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The currently dispatched route instance.
     *
     * @var \HuangYi\Swoole\Routing\Route
     */
    protected $current;

    /**
     * The request currently being dispatched.
     *
     * @var \HuangYi\Swoole\Foundation\JsonRpc\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    public $middlewarePriority = [];

    /**
     * The route group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * The valid delimiters of action.
     *
     * @var array
     */
    public static $actionDelimiters = ['::', '@'];

    /**
     * Create a new Router instance.
     *
     * @param  \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->routes = new RouteCollection;
        $this->container = $container;
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param  array $attributes
     * @param  \Closure|string $routes
     * @return void
     */
    public function group(array $attributes, $routes)
    {
        $this->updateGroupStack($attributes);

        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (! empty($this->groupStack)) {
            $attributes = RouteGroup::merge($attributes, end($this->groupStack));
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param  array $new
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        return RouteGroup::merge($new, end($this->groupStack));
    }

    /**
     * Load the provided routes.
     *
     * @param  \Closure|string $routes
     * @return void
     */
    protected function loadRoutes($routes)
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            $router = $this;

            require $routes;
        }
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  string $method
     * @param  \Closure|array|string|null $action
     * @return \HuangYi\Swoole\Routing\Route
     */
    public function add($method, $action = null)
    {
        return $this->routes->add($this->createRoute($method, $action));
    }

    /**
     * Create a new route instance.
     *
     * @param  string $method
     * @param  mixed $action
     * @return \HuangYi\Swoole\Routing\Route
     */
    protected function createRoute($method, $action)
    {
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute($method, $action);

        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        return $route;
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        if (! empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param  string $class
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
            ? $group['namespace'] . '\\' . $class : $class;
    }

    /**
     * Create a new Route object.
     *
     * @param  string $method
     * @param  mixed $action
     * @return \HuangYi\Swoole\Routing\Route
     */
    protected function newRoute($method, $action)
    {
        return (new Route($method, $action))
            ->setContainer($this->container);
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \HuangYi\Swoole\Routing\Route $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    /**
     * Dispatch the request to the application.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request);
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Response
     */
    public function dispatchToRoute(Request $request)
    {
        $route = $this->findRoute($request);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->runRouteWithinStack($route, $request);

        return $this->prepareResponse($request, $response);
    }

    /**
     * Find the route matching a given request.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return \HuangYi\Swoole\Routing\Route
     */
    protected function findRoute($request)
    {
        $this->current = $route = $this->routes->match($request);

        $this->container->instance(Route::class, $route);

        return $route;
    }

    /**
     * Run the given route within a Stack "onion" instance.
     *
     * @param  \HuangYi\Swoole\Routing\Route $route
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return mixed
     */
    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
            $this->container->make('middleware.disable') === true;

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->container))
            ->send($request)
            ->through($middleware)
            ->then(function ($request) use ($route) {
                return $this->prepareResponse(
                    $request, $route->run()
                );
            });
    }

    /**
     * Gather the middleware for the given route with resolved class names.
     *
     * @param  \HuangYi\Swoole\Routing\Route $route
     * @return array
     */
    public function gatherRouteMiddleware(Route $route)
    {
        $middleware = collect($route->gatherMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten();

        return $this->sortMiddleware($middleware);
    }

    /**
     * Sort the given middleware by priority.
     *
     * @param  \Illuminate\Support\Collection $middlewares
     * @return array
     */
    protected function sortMiddleware(Collection $middlewares)
    {
        return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
    }

    /**
     * Create a response instance from the given value.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @param  mixed $response
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Response
     */
    public function prepareResponse($request, $response)
    {
        if (! $response instanceof Response) {
            $result = $response;
            $response = new Response;

            if ($result instanceof Arrayable) {
                $result = $result->toArray();
            } elseif (! is_scalar($result) && ! is_array($result)) {
                $result = (string) $result;
            }

            $response->setResult($result);
        }

        return $response->prepare($request);
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param  string $name
     * @param  string $class
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Check if a middlewareGroup with the given name exists.
     *
     * @param  string $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->middlewareGroups);
    }

    /**
     * Get all of the defined middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Register a group of middleware.
     *
     * @param  string $name
     * @param  array $middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string $group
     * @param  string $middleware
     * @return $this
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        if (isset($this->middlewareGroups[$group]) && ! in_array($middleware, $this->middlewareGroups[$group])) {
            array_unshift($this->middlewareGroups[$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param  string $group
     * @param  string $middleware
     * @return $this
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        if (! array_key_exists($group, $this->middlewareGroups)) {
            $this->middlewareGroups[$group] = [];
        }

        if (! in_array($middleware, $this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group][] = $middleware;
        }

        return $this;
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the router.
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get the request currently being dispatched.
     *
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \HuangYi\Swoole\Routing\Route
     */
    public function getCurrentRoute()
    {
        return $this->current();
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \HuangYi\Swoole\Routing\Route
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a route with the given name exists.
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->routes->hasNamedRoute($name);
    }

    /**
     * Get the current route name.
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        return $this->current() ? $this->current()->getName() : null;
    }

    /**
     * Alias for the "currentRouteNamed" method.
     *
     * @return bool
     */
    public function is()
    {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->currentRouteName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route matches a given name.
     *
     * @param  string $name
     * @return bool
     */
    public function currentRouteNamed($name)
    {
        return $this->current() ? $this->current()->getName() == $name : false;
    }

    /**
     * Get the current route action.
     *
     * @return string|null
     */
    public function currentRouteAction()
    {
        if (! $this->current()) {
            return;
        }

        $action = $this->current()->getAction();

        return isset($action['controller']) ? $action['controller'] : null;
    }

    /**
     * Alias for the "currentRouteUses" method.
     *
     * @return bool
     */
    public function uses()
    {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->currentRouteAction())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route action matches a given action.
     *
     * @param  string $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        return $this->currentRouteAction() == $action;
    }

    /**
     * Get the underlying route collection.
     *
     * @return \HuangYi\Swoole\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set the route collection instance.
     *
     * @param  \HuangYi\Swoole\Routing\RouteCollection $routes
     * @return void
     */
    public function setRoutes(RouteCollection $routes)
    {
        foreach ($routes as $route) {
            $route->setRouter($this)->setContainer($this->container);
        }

        $this->routes = $routes;

        $this->container->instance('swoole.routes', $this->routes);
    }
}
