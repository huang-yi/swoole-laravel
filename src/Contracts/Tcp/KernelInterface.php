<?php

namespace HuangYi\Swoole\Contracts\Tcp;

use HuangYi\Swoole\Tcp\Request;

interface KernelInterface
{
    /**
     * Handle Tcp request.
     *
     * @param \HuangYi\Swoole\Tcp\Request $request
     * @return \HuangYi\Swoole\Tcp\Response
     * @throws \Exception
     */
    public function handle(Request $request);
}
