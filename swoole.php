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

$server = new HuangYi\Swoole\Servers\HttpServer();

$server->run();
