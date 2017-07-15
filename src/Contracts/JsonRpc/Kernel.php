<?php

namespace HuangYi\Swoole\Contracts\JsonRpc;

interface Kernel
{
    /**
     * Handle an incoming JSON-RPC request.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @return \HuangYi\Swoole\Foundation\JsonRpc\Response
     */
    public function handle($request);

    /**
     * Call the terminate method on any terminable middleware.
     *
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Request $request
     * @param  \HuangYi\Swoole\Foundation\JsonRpc\Response $response
     * @return void
     */
    public function terminate($request, $response);
}
