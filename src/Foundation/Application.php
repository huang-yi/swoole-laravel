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
     * The bootstrap classes for the Laravel application.
     *
     * @var array
     */
    protected $bootstrappers = [
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'Illuminate\Foundation\Bootstrap\LoadConfiguration',
        'Illuminate\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',
        'Illuminate\Foundation\Bootstrap\BootProviders',
    ];

    /**
     * Return an Application instance.
     *
     * @return \HuangYi\Swoole\Foundation\Application
     */
    public static function make()
    {
        return new static();
    }

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->create();
    }

    /**
     * Run the Laravel application.
     */
    public function run()
    {

    }

    /**
     * Create the Laravel application.
     */
    protected function create()
    {
        if ( ! ($framework = $this->detectFramework()) ) {
            throw new UnexpectedFramework('Only support Laravel or Lumen framework!');
        }

        $this->application = $this->getLaravelApplication();

        if ( $this->framework == 'laravel' ) {
            $this->application->bootstrapWith($this->bootstrappers);
        }
    }

    /**
     * Detect framework.
     *
     * @return string|bool
     */
    public function detectFramework()
    {
        if ( class_exists(LaravelApplication::class) ) {
            $this->framework = 'laravel';
        } elseif ( class_exists(LumenApplication::class) ) {
            $this->framework =  'lumen';
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
        if ( $this->application instanceof Container ) {
            return $this->application;
        }

        return $this->application = require base_path() . '/bootstrap/app.php';
    }

}