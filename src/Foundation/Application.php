<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Swoole\Foundation;

use HuangYi\Exceptions\UnexpectedFramework;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;

class Application
{
    /**
     * Current framework: 'laravel' or 'lumen'.
     *
     * @var string
     */
    protected $framework;

    /**
     * Laravel Application.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $application;

    /**
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $kernel;

    /**
     * Return an Application instance.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    public static function make()
    {
        return new static();
    }

    /**
     * Application constructor.
     *
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    public function __construct()
    {
        $this->create();
    }

    /**
     * Run the Laravel application.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    public function run($request)
    {
        if ($this->framework == 'laravel') {
            $response = $this->runLaravel($request);
        } elseif ($this->framework == 'lumen') {
            $response = $this->runLumen($request);
        } else {
            throw new UnexpectedFramework('Only support Laravel or Lumen framework!');
        }

        return $response;
    }

    /**
     * Run Laravel framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function runLaravel($request)
    {
        $kernel = $this->getLaravelApplication()->make(Kernel::class);

        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * Run Lumen framework.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function runLumen($request)
    {
        $application = $this->getLaravelApplication();

        // Reflections
        $reflection = new \ReflectionObject($application);

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);

        $dispatch = $reflection->getMethod('dispatch');

        $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
        $callTerminableMiddleware->setAccessible(true);

        // Run
        $response = $dispatch->invoke($application, $request);

        if (count($middleware->getValue($application)) > 0) {
            $callTerminableMiddleware->invoke($application, $response);
        }

        return $response;
    }

    /**
     * Create the Laravel application.
     *
     * @throws \HuangYi\Exceptions\UnexpectedFramework
     */
    protected function create()
    {
        if (! ($this->framework = $this->detectFramework())) {
            throw new UnexpectedFramework('Only support Laravel or Lumen framework!');
        }

        $this->application = $this->getLaravelApplication();

        if ($this->framework == 'laravel') {
            $bootstrappers = $this->getLaravelBootstrappers();
            $this->application->bootstrapWith($bootstrappers);
        }
    }

    /**
     * Detect framework.
     *
     * @return string|bool
     */
    public function detectFramework()
    {
        if (class_exists(LaravelApplication::class)) {
            $this->framework = 'laravel';
        } elseif (class_exists(LumenApplication::class)) {
            $this->framework = 'lumen';
        } else {
            return false;
        }

        return $this->framework;
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    protected function getLaravelApplication()
    {
        if ($this->application instanceof Container) {
            return $this->application;
        }

        return $this->application = require base_path() . '/bootstrap/app.php';
    }

    /**
     * @return \Illuminate\Contracts\Http\Kernel
     */
    protected function getLaravelHttpKernel()
    {
        if ($this->kernel instanceof Kernel) {
            return $this->kernel;
        }

        return $this->kernel = $this->getLaravelApplication()->make(Kernel::class);
    }

    /**
     * Get Laravel bootstrappers.
     *
     * @return array
     */
    protected function getLaravelBootstrappers()
    {
        $kernel = $this->getLaravelHttpKernel();

        // Reflect Kernel
        $reflection = new \ReflectionObject($kernel);

        $bootstrappersMethod = $reflection->getMethod('bootstrappers');
        $bootstrappersMethod->setAccessible(true);

        $bootstrappers = $bootstrappersMethod->invoke($kernel);

        array_splice($bootstrappers, -2, 0, ['Illuminate\Foundation\Bootstrap\SetRequestForConsole']);

        return $bootstrappers;
    }

}