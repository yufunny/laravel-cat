###Laravel-Cat
Control concurrency and throttle  for laravel.

laravel路由并发/限流控制

###用法
1. 添加ServiceProvider
    - laravel:在config/app.php 中的providers添加：Yufunny\LaravelCat\CatServiceProvider::class
    - lumen:在bootstrap/app.php 中添加：
$app->register(Yufunny\LaravelCat\CatServiceProvider::class);

2. 路由中使用

- 限流
```php
    $router->group(['middleware' => 'cat.t:uid-ip%60%5'], function () use ($router) {
        $router->get('foo', function() {
            return 'hello';
        });
    });
```
表示同一个ip、uid在60秒内最大请求5次

- 并发
```php
    $router->group(['middleware' => 'cat.c:uid%ip'], function () use ($router) {
        $router->get('bar', function() {
            return 'hello';
        });
    });
```
表示同一个ip、uid不能并发请求


###说明
限流中间件：cat.t,参数格式为param1-param2%duration%max。表示请求参数中有param1、param2时，相同的参数值在一段时间内（单位秒）请求次数不能超过max次。
并发中间件：cat.c,参数格式为param1%param2%param3,表示param1，param2，param3相同的参数值不能并发请求。