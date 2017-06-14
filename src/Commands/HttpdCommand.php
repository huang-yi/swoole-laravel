<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Swoole\Commands;

use HuangYi\Swoole\Servers\HttpServer;
use HuangYi\Swoole\Traits\ProcessTrait;
use Illuminate\Console\Command;

class HttpdCommand extends Command
{
    use ProcessTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:httpd {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the swoole http server.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->isRunning($this->getPID())) {
            $this->error('Failed! swoole:httpd is already running.');
            exit(1);
        }

        $this->info('Starting swoole:httpd...');

        $this->info('> (You can run this command to ensure the ' .
            'swoole:httpd process is running: ps aux|grep "swoole:httpd")');

        // Before starting a swoole http server, we should clear out the
        // common caches. You can also write your own logic in a Closure
        // by configuring the 'before_start' option. This ensures our program
        // works correctly.
        $this->beforeStart();

        $this->start();

        $this->info('> Started');
    }

    /**
     * Before start handler.
     */
    protected function beforeStart()
    {
        $callback = app('config')->get('swoole.before_start');

        if ($callback instanceof \Closure) {
            return $callback();
        }

        // By default, we will clear the APC or OPcache.
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Start the swoole http server.
     */
    protected function start()
    {
        $server = new HttpServer;

        $server->run();
    }
}
