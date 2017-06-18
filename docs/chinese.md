## 安装

使用以下命令即可快速安装拓展包：

```
$ composer require huang-yi/swoole-laravel:~1.0
```

或者编辑项目的`composer.json`文件，将拓展包的信息添加至`require`对象，然后执行`composer update`命令即可：

```json
{
    "require": {
        "huang-yi/swoole-laravel": "~1.0"
    }
}
```

> 该拓展包依赖于Swoole，请务必确保你的机器安装上了Swoole拓展。快速安装命令：`pecl install swoole`，详情请参考[Swoole官方网站](https://wiki.swoole.com/wiki/page/6.html)。

## 注册服务

安装好拓展包后，你需要将服务注册到应用中去，Laravel和Lumen的注册方式有所不同：

**Laravel**

编辑项目的配置文件`config/app.php`，找到`providers`数组，然后将服务类添加到最后：

```php
[
    'providers' => [
        HuangYi\Swoole\SwooleServiceProvider::class,
    ],
]
```

**Lumen**

编辑项目的启动文件`bootstrap/app.php`，然后添加以下代码：

```php
$app->register(HuangYi\Swoole\SwooleServiceProvider::class);
```

## 配置信息

> 该拓展包的配置十分简单，只要确保端口1215不被其他程序所占用，你就可以选择不配置任何其他东西。

如果你使用的是Laravel框架，你可以使用以下命令来生成配置文件：

```
$ php artisan vendor:publish --provider="HuangYi\Swoole\SwooleServiceProvider" --tag=config
```

然后你就可以在`config/`目录下找到该拓展包的配置文件`swoole.php`，使用你常用的编辑器打开它，一共有6个配置项：

`name`: 设置服务名

`host`: swoole_http_server监听的IP地址。0.0.0.0表示监听所有地址，127.0.0.1表示监听本地（默认），或者指定一个确定的IP地址。你也可以在`.env`文件中使用`SWOOLE_HOST`来配置该值。

`port`: swoole_http_server监听的端口。默认为1215，你也可以在`.env`文件中使用`SWOOLE_PORT`来配置该值。

`server`: swoole_http_server的配置项。只允许配置[Swoole官方文档](https://wiki.swoole.com/wiki/page/274.html)规定的配置选项，你可以根据需求自行添加。如果你想在`.env`中做配置，可以使用形如`SWOOLE_SERVER_XXX`格式的键名，系统会自动解析。

`options`: 这项配置用于拓展`server`的选项。如果新版本的Swoole添加了新的配置项，而拓展并未及时更新，你可以自行在此配置项中申明新添加的配置项。在`.env`文件中可用`SWOOLE_OPTIONS`来配置，使用逗号(,)隔开。

`before_start`: 服务启动的前置钩子。该配置只允许使用闭包函数。

如果你使用Lumen框架，可以按照上述规则直接在`.env`文件中完成配置。

## 运行服务

> swoole_http_server只能运行在cli模式下，因此该拓展包也提供了方便的artisan命令来做管理。

启动swoole_http_server：

```
$ php artisan swoole:http start
```

停止swoole_http_server：

```
$ php artisan swoole:http stop
```

重启swoole_http_server：

```
$ php artisan swoole:http restart
```

重载swoole_http_server：

```
$ php artisan swoole:http reload
```

## 配置Nginx

> swoole_http_server对Http协议的支持并不完整，建议仅作为应用服务器，并且在前端增加Nginx作为代理。

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # 确保public目录下没有名字为not_exists的文件
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

        # 如果使用了https协议
        # proxy_set_header HTTPS "on";

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```

### 注意事项

每次发布新代码需要重载或重启swoole_http_server，因为swoole_http_server启动时会提前加载应用框架，使其常驻内存，这也是swoole_http_server高性能的原因之一。
