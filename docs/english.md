## Installation

You can quickly complete the installation by using this command:

```
$ composer require huang-yi/swoole-laravel:~1.0
```

You can also add the package information in your `composer.json` file, and then run the command `composer update`:

```json
{
    "require": {
        "huang-yi/swoole-laravel": "~1.0"
    }
}
```

> This package is relied on Swoole. Please make sure that your machine has been installed the Swoole extension. Using this command to install quickly: `pecl install swoole`. Visit the [official website](https://wiki.swoole.com/wiki/page/6.html) for more information.

## Service Provider

You need to register service provider for your application after completed installation. There is different way to register between Laravel and Lumen:

**Laravel**

Add this line of the code to the `providers` array which located in the `config/app.php` file:

```php
[
    'providers' => [
        HuangYi\Swoole\SwooleServiceProvider::class,
    ],
]
```

**Lumen**

Append this line of the code to the `bootstrap/app.php` file:

```php
$app->register(HuangYi\Swoole\SwooleServiceProvider::class);
```

## Configuration

> This package has very simple configurations. Just make sure the port `1215` is not used by other process if you are lazy to check other configurations.

If you are using Laravel framework, run this command to generate the configuration file:

```
$ php artisan vendor:publish --provider="HuangYi\Swoole\SwooleServiceProvider" --tag=config
```

And then, you will find a new file `swoole.php` under the folder `config/`, open it with the editor you like:

`host`: The IP address of the SwooleHttpServer listening. The default value is 127.0.0.1. You can also use `SWOOLE_HOST` to configure it in the file `.env`.

`port`: The port of the SwooleHttpServer listening. The default value is 1215. You can also use `SWOOLE_PORT` to configure it in the file `.env`.

`server`: The SwooleServer's configuration options. Please read the [official document](https://wiki.swoole.com/wiki/page/274.html) for more configuration options. You can freely add options according to your requirements. You can also configure it by using the format like `SWOOLE_SERVER_XXX` in the file `.env`.

## Command

> The SwooleHttpServer can only run in cli environment, and this package provides convenient artisan commands to manage it.

Start the SwooleHttpServer:

```
$ php artisan swoole:http start
```

Stop the SwooleHttpServer:

```
$ php artisan swoole:http stop
```

Restart the SwooleHttpServer:

```
$ php artisan swoole:http restart
```

## Nginx Configuration

> SwooleHttpServer support for Http is not complete, it is recommended that only as the application server, and use the Nginx as a proxy.

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    
    location / {
        if (!-e $request_filename) {
            proxy_pass http://127.0.0.1:1215;
        }
    }
}
```

## Notice

You should restart the SwooleHttpServer after released your code. Because the Laravel program will be kept in memory after the SwooleHttpServer started. That is one of the reasons why the SwooleHttpServer has high performance.
