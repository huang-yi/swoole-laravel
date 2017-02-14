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
use Illuminate\Console\Command;
use Swoole\Http\Server;

class HttpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:http {action : start|stop|restart}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole http server command.';

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
     * Run swoole http server.
     */
    protected function start()
    {
        if ( $this->isRunning() ) {
            $this->error('Swoole http server process is already running.');
            exit(1);
        }

        $swooleHttpServer = new Server('127.0.0.1', '1215');
        $httpServer = new HttpServer($swooleHttpServer);

        $httpServer->run();
    }

    /**
     * Stop swoole http server.
     */
    protected function stop()
    {
        if ( ! $this->isRunning() ) {
            $this->error("There is no Swoole http server process running.");
            exit(1);
        }

        $pid = $this->getPID();

        $this->sendSignal($pid, SIGINT, 15);

        if ( $this->isRunning() ) {
            $this->sendSignal($pid, SIGTERM, 15);
        }

        if ( $this->isRunning() ) {
            $this->sendSignal($pid, SIGKILL, 0);
        }

        if ( $this->isRunning() ) {
            $this->error('Unable to stop Swoole http server process.');
            exit(1);
        }
    }

    /**
     * Restart swoole http server.
     */
    protected function restart()
    {
        $this->stop();
        $this->start();
    }

    /**
     * @param $pid
     * @param $sig
     * @param $wait
     */
    protected function sendSignal($pid, $sig, $wait)
    {
        posix_kill($pid, $sig);

        if ( $wait ) {
            $start = time();

            do {
                $this->isRunning();

                usleep(100000);
            } while ( time() < $start + $wait );
        }
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if ( ! in_array($this->action, ['start', 'stop', 'restart']) ) {
            $this->error("Invalid argument '{$this->action}'. Expected 'start', 'stop' or 'restart'.");
            exit(1);
        }
    }

    /**
     * If Swoole process is running.
     *
     * @return bool
     */
    protected function isRunning()
    {
        $pid = $this->getPID();

        if ( ! $pid ) {
            return false;
        }

        $isRunning = posix_kill($pid, 0);

        if ( posix_get_last_error() == 1 ) {
            $isRunning = true;
        }

        if ( ! $isRunning ) {
            $this->removePIDFile();
        }

        return $isRunning;
    }

    /**
     * Get PID.
     *
     * @return int|null
     */
    protected function getPID()
    {
        if (  $this->pid ) {
            return $this->pid;
        }

        $pid = null;
        $path = $this->getPIDPath();

        if ( file_exists($path) ) {
            $pid = (int) file_get_contents($path);

            if ( ! $pid ) {
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
        return app('config')->get('swoole.pid_file');
    }

    /**
     * Remove PID file.
     */
    protected function removePIDFile()
    {
        if ( file_exists($this->getPIDPath()) ) {
            unlink($this->getPIDPath());
        }
    }
}
