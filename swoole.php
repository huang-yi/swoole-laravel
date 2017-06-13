<?php

/*
|--------------------------------------------------------------------------
| Run The Swoole Http Server
|--------------------------------------------------------------------------
|
| Let's run the swoole http server.
| Enjoy yourself!
|
*/

require __DIR__ . '/../../autoload.php';

$server = new HuangYi\Swoole\Servers\HttpServer();

$server->run();
