<?php

namespace HuangYi\Swoole\Config;

use Illuminate\Contracts\Config\Repository as BaseRepository;

class Repository
{
    /**
     * Swoole Server allowed configuration options.
     *
     * @var array
     */
    public static $options = [
        'reactor_num', 'worker_num', 'max_request', 'max_conn',
        'task_worker_num', 'task_ipc_mode', 'task_max_request', 'task_tmpdir',
        'dispatch_mode', 'message_queue_key', 'daemonize', 'backlog',
        'log_file', 'log_level', 'heartbeat_check_interval',
        'heartbeat_idle_time', 'open_eof_check', 'open_eof_split',
        'package_eof', 'open_length_check', 'package_length_type',
        'package_length_func', 'package_max_length', 'open_cpu_affinity',
        'cpu_affinity_ignore', 'open_tcp_nodelay', 'tcp_defer_accept',
        'ssl_cert_file', 'ssl_method', 'user', 'group', 'chroot', 'pid_file',
        'pipe_buffer_size', 'buffer_output_size', 'socket_buffer_size',
        'enable_unsafe_event', 'discard_timeout_request', 'enable_reuse_port',
        'ssl_ciphers', 'enable_delay_receive', 'open_http_protocol',
        'open_http2_protocol', 'open_websocket_protocol'
    ];

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $repository;

    /**
     * Get specified server's host.
     *
     * @param string $protocol
     * @return string
     */
    public function getServerHost($protocol)
    {
        $key = sprintf('swoole.servers.%s.host', $protocol);

        return $this->repository->get($key);
    }

    /**
     * Get specified server's port.
     *
     * @param string $protocol
     * @return int
     */
    public function getServerPort($protocol)
    {
        $key = sprintf('swoole.servers.%s.port', $protocol);

        return $this->repository->get($key);
    }

    /**
     * Get specified server's options.
     *
     * @param string $protocol
     * @return array
     */
    public function getServerOptions($protocol)
    {
        $key = sprintf('swoole.servers.%s.options', $protocol);
        $options = (array) $this->repository->get($key, []);
        $options = $this->mergeServerOptions($protocol, $options);

        return $options;
    }

    /**
     * Get specified server's pid file path.
     *
     * @param $protocol
     * @return string
     */
    public function getServerPidFile($protocol)
    {
        $key = sprintf('swoole.servers.%s.options.pid_file', $protocol);

        return $this->repository->get($key);
    }

    /**
     * @param string $protocol
     * @param array $options
     * @return array
     */
    protected function mergeServerOptions($protocol, array $options)
    {
        $envOptions = $this->getServerEnvOptions($protocol);

        return array_merge($options, $envOptions);
    }

    /**
     * @param string $protocol
     * @return array
     */
    protected function getServerEnvOptions($protocol)
    {
        $envOptions = [];

        $protocol = strtoupper($protocol);
        $keys = $this->getOptionKeys();

        foreach ($keys as $key) {
            $envKey = sprintf('SWOOLE_SERVERS_%s_%s', $protocol, strtoupper($key));
            $envValue = env($envKey);

            if (! is_null($envValue)) {
                $envOptions[$key] = $envValue;
            }
        }

        return $envOptions;
    }

    /**
     * @return array
     */
    protected function getOptionKeys()
    {
        $extendOptions = $this->repository->get('swoole.options', []);

        return array_merge(self::$options, $extendOptions);
    }

    /**
     * @param \Illuminate\Contracts\Config\Repository $repository
     */
    public function setRepository(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $function
     * @param array $arguments
     * @return mixed
     */
    public function __call($function, $arguments)
    {
        return $this->repository->$function(...$arguments);
    }
}
