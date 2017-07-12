<?php

namespace HuangYi\Swoole\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \HuangYi\Swoole\Routing\Route add(string $method, \Closure | array | string $action)
 * @method static void group(array $attributes, \Closure $callback)
 *
 * @see \HuangYi\Swoole\Routing\Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.router';
    }
}
