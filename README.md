# Swoole-Laravel

[Swoole](https://github.com/swoole) provides a lot of high performance services. This package implements a http server by using `Swoole\Http\Server`. The purpose is to improve the performance of web application which based on Laravel or Lumen.

## Version Compatibility

| PHP     | Laravel | Lumen | Swoole  |
|:-------:|:-------:|:-----:|:-------:|
| >=5.5.9 | ~5.1    | ~5.1  | >=1.8.7 |

## Quick Start

**Firstly**, require this package to your project:

```
$ composer require huang-yi/swoole-laravel:~1.1
```

**Secondly**, register service provider to your application:

If you are using Laravel, add this line of the code to the `providers` array which located in the `config/app.php` file:

```php
[
    'providers' => [
        HuangYi\Swoole\SwooleServiceProvider::class,
    ],
]
```

If you are using Lumen, append this line of the code to the `bootstrap/app.php` file:

```php
$app->register(HuangYi\Swoole\SwooleServiceProvider::class);
```

**Thirdly**, run this command to start the **swoole_http_server**.

```
$ php artisan swoole:http start
```

**Finally**, edit your nginx configuration file:

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # Ensure that there is no such file named "not_exists"
        # in your "public" directory.
        try_files /not_exists @swoole;
    }

    location / {
        try_files $uri $uri/ @swoole;
    }

    location @swoole {
        set $suffix "";
        
        if ($uri = /index.php) {
            set $suffix "/";
        }
    
        proxy_set_header Host $host;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

        # IF https
        # proxy_set_header HTTPS "on";

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```

## Documentation

- [English](docs/english.md)
- [简体中文](docs/chinese.md)

## Support

Bugs and feature request are tracked on [Github](https://github.com/huang-yi/swoole-laravel/issues).

## License

The Swoole Laravel package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
