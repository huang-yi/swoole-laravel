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

use HuangYi\Swoole\Traits\ProcessTrait;
use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessUtils;

class HttpctlCommand extends Command
{
    use ProcessTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:httpctl {action : start|stop|restart}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The swoole http server controller.';

    /**
     * The console command action. start|stop|restart
     *
     * @var string
     */
    protected $action;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->initAction();

        $this->runAction();
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if (! in_array($this->action, ['start', 'stop', 'restart'])) {
            $this->error("Invalid argument '{$this->action}'. Expected 'start', 'stop' or 'restart'.");
            exit(1);
        }
    }

    /**
     * Run the command action.
     */
    protected function runAction()
    {
        $this->{$this->action}();
    }

    /**
     * Start.
     */
    protected function start()
    {
        passthru($this->startCommand());
    }

    /**
     * Stop.
     */
    protected function stop()
    {
        $pid = $this->getPID();

        if (! $this->isRunning($pid)) {
            $this->error("Failed! There is no swoole:httpd running.");
            exit(1);
        }

        $this->info('Stopping swoole:httpd...');

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->error('Unable to stop the swoole:httpd.');
            exit(1);
        }

        // I don't known why Swoole didn't trigger onShutdown after sending SIGTERM.
        // So we should manually remove the pid file.
        $this->removePIDFile();

        $this->info('> Stopped');
    }

    /**
     * Restart.
     */
    protected function restart()
    {
        $this->stop();

        $this->start();
    }

    /**
     * @return string
     */
    protected function startCommand()
    {
        chdir(app()->basePath());

        $name = app('config')->get('swoole.name');
        $name = $name ?: '';

        return sprintf('%s artisan swoole:httpd%s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            $name
        );
    }
}
