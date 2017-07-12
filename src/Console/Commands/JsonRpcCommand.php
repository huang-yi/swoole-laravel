<?php

namespace HuangYi\Swoole\Console\Commands;

use HuangYi\Swoole\Servers\JsonRpcServer;
use Illuminate\Console\Command;
use Swoole\Process;

class JsonRpcCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:jsonrpc {action : start|stop|restart}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process swoole_jsonrpc_server controller.';

    /**
     * The console command action. start|stop|restart
     *
     * @var string
     */
    protected $action;

    /**
     *
     * The PID.
     *
     * @var int
     */
    protected $pid;

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
     * Run action.
     */
    protected function runAction()
    {
        $this->{$this->action}();
    }

    /**
     * Start swoole_jsonrpc_server.
     */
    protected function start()
    {
        if ($this->isRunning($this->getPID())) {
            $this->error('Failed! swoole_jsonrpc_server process is already running.');
            exit(1);
        }

        $this->info('Starting swoole_jsonrpc_server...');

        $this->info('> (You can run this command to ensure the ' .
            'swoole_jsonrpc_server process is running: ps aux|grep "swoole")');

        app(JsonRpcServer::class)->run();
    }

    /**
     * Stop swoole_jsonrpc_server.
     */
    protected function stop()
    {
        $pid = $this->getPID();

        if (! $this->isRunning($pid)) {
            $this->error("Failed! There is no swoole_jsonrpc_server process running.");
            exit(1);
        }

        $this->info('Stopping swoole_jsonrpc_server...');

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->error('Unable to stop the swoole_jsonrpc_server process.');
            exit(1);
        }

        // I don't known why Swoole didn't trigger onShutdown after sending SIGTERM.
        // So we should manually remove the pid file.
        $this->removePIDFile();

        $this->info('> success');
    }

    /**
     * Restart swoole_jsonrpc_server.
     */
    protected function restart()
    {
        $this->stop();
        $this->start();
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
     * If Swoole process is running.
     *
     * @param int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (! $pid) {
            return false;
        }

        Process::kill($pid, 0);

        return ! swoole_errno();
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     * @return bool
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);

        if ($wait) {
            $start = time();

            do {
                if (! $this->isRunning($pid)) {
                    break;
                }

                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning($pid);
    }

    /**
     * Get PID.
     *
     * @return int|null
     */
    protected function getPID()
    {
        if ($this->pid) {
            return $this->pid;
        }

        $pid = null;
        $path = $this->getPIDPath();

        if (file_exists($path)) {
            $pid = (int) file_get_contents($path);

            if (! $pid) {
                $this->removePIDFile();
            } else {
                $this->pid = $pid;
            }
        }

        return $this->pid;
    }

    /**
     * Get PID file path.
     *
     * @return string
     */
    protected function getPIDPath()
    {
        return app('config')->get('swoole.servers.jsonrpc.options.pid_file');
    }

    /**
     * Remove PID file.
     */
    protected function removePIDFile()
    {
        if (file_exists($this->getPIDPath())) {
            unlink($this->getPIDPath());
        }
    }
}
