# hprose-lumen

基于 [hprose/hprose-php](https://github.com/hprose/hprose-php/wiki) 开发的Lumen扩展：[hprose-lumen](https://github.com/trangfoo/hprose-lumen)

开发背景：最近需要在lumen框架中实现rpc的功能，于是在网上找了相关的资料，进行了一些拓展

参考了：[Laravel-hprose](https://github.com/zhuqipeng/laravel-hprose) | [Lumen-hprose](https://github.com/lumening/lumen-hprose)

## 版本要求

```
Lumen>=5.2
【注】本项目是在Lumen8下进行的测试
```

## 安装

直接使用
```shell
composer require trangfoo/hprose-lumen
```

## 使用**lumen**配置
1. 在 bootstrap/app.php 中引入hprose配置、注册 ServiceProvider 和 Facade
   ```php
        $app->configure('hprose');
   ```
    ```php
       $app->register(Trangfoo\HproseLumen\ServiceProvider::class);
    ```
    ```php
        $app->withFacades(true, [
            // ...
            'Trangfoo\HproseLumen\Facades\Router' => 'HproseLumenRouter',
        ]);
    ```
2. 在 app/Console/Kernel.php 添加 vendor publish
    ```php
        protected $commands = [
        //...
        \Laravelista\LumenVendorPublish\VendorPublishCommand::class,
        ];
    ```

3. 配置.env文件
   
   完整配置
   ```
   #[RPC]
   #RPC服务（监听端口、主机）
   HPROSE_PORT=88
   HPROSE_URIS=["tcp://0.0.0.0:${HPROSE_PORT}"]
   
   #开启范例路由
   HPROSE_DEMO=true
   
   #请求不通过返回码及信息
   HPROSE_REJECT_CODE=0
   HPROSE_REJECT_MSG=Server拒绝本次请求
   
   #RPC连接密钥
   HPROSE_SECRET=123456789
   
   #RPC超时限制（秒）
   HPROSE_TIMEOUT=60
   ```

   监听地址列表，字符串json格式数组
    ```
    #RPC服务（监听端口、主机）
    HPROSE_PORT=88
    HPROSE_URIS=["tcp://0.0.0.0:${HPROSE_PORT}"]
    ```

   是否启用demo方法，true开启 false关闭，开启后将自动对外发布一个远程调用方法 `demo`
   客户端可调用：$client->demo()
    ```
    #开启范例路由
    HPROSE_DEMO=true
    ```

   客户端与服务端通信时，如果发生鉴权失败、超时等返回失败信息；
   RPC通信的鉴权密钥、超时时间限制
   ```
   #请求不通过返回码及信息
   HPROSE_REJECT_CODE=0
   HPROSE_REJECT_MSG=Server拒绝本次请求

   #RPC连接密钥
   HPROSE_SECRET=123456789

   #RPC超时限制（秒）
   HPROSE_TIMEOUT=60
   ```


4. 创建`配置`和`路由`文件：
    ```shell
    php artisan vendor:publish --provider="Trangfoo\HproseLumen\ServiceProvider"
    ```
   >应用根目录下的`config`目录下会自动生成新文件`hprose.php`
   >
   >应用根目录下的`routes`目录下会自动生成新文件`rpc.php`

## 使用

### 路由

路由文件
```
routes/rpc.php
```

添加路由方法
```php
\HproseLumenRouter::add(string $name, string|callable $action, array $options = []);
```
- string $name 可供客户端远程调用的方法名
- string|callable $action 类方法，格式：App\Controllers\User@update
- array $options 是一个关联数组，它里面包含了一些对该服务函数的特殊设置，详情请参考hprose-php官方文档介绍 [链接](https://github.com/hprose/hprose-php/wiki/06-Hprose-%E6%9C%8D%E5%8A%A1%E5%99%A8#addfunction-%E6%96%B9%E6%B3%95)

发布远程调用方法 `getUserByName` 和 `update`
```php
\HproseLumenRouter::add('getUserByName', function ($name) {
    return 'name: ' . $name;
});

\HproseLumenRouter::add('userUpdate', 'App\Controllers\User@update', ['model' => \Hprose\ResultMode::Normal]);
```

控制器
```php
<?php

namespace App\Controllers;

class User
{
    public function update($name)
    {
        return 'update name: ' . $name;
    }
    
    public function getUserById($id)
    {
        return [
            'id'=>$id,
            'name'=>'lumen',
            'email'=>'',
            'url'=>'http://www.lumen.fun',
        ];
    }
}
```

客户端调用、加入鉴权Handler
```php

$client = new \Hprose\Socket\Client('tcp://127.0.0.1:8888', false);
$client->addInvokeHandler(array(new Trangfoo\HproseLumen\Handler\AuthFilter(), 'inputInvokeHandler'));  //添加鉴权InvokeHandler
$client->getUserByName('lumen');
$client->userUpdate('lumen');
```

路由组
```php
\HproseLumenRouter::group(array $attributes, callable $callback);
```
- array $attributes 属性 ['namespace' => '', 'prefix' => '']
- callable $callback 回调函数

```php
\HproseLumenRouter::group(['namespace' => 'App\Controllers'], function ($route) {
    $route->add('getUserByName', function ($name) {
        return 'name: ' . $name;
    });
    
    $route->add('getUserById', 'User@getUserById');
    $route->add('userUpdate', 'User@update');
});
```
客户端调用
```php
$client->getUserByName('lumen');
$client->userUpdate('lumen');
```

前缀
```php
\HproseLumenRouter::group(['namespace' => 'App\Controllers', 'prefix' => 'user'], function ($route) {
    $route->add('getByName', function ($name) {
        return 'name: ' . $name;
    });

    $route->add('update', 'User@update');
});
```
客户端调用
```php
$client->user->getByName('lumen');
$client->user->update('lumen');
// 或者
$client->user_getByName('lumen');
$client->user_update('lumen');
```

如果服务端出现 exception ，因为hprose 没有返回code(已经和开发者确认)，需要将code 合并到message用json方式包裹返回
```php
try{
    $client->user->getByName('lumen');
}catch(\Exception $e){
    $info = json_decode($e->getMessage(),true);
    $message = $info['message'];
    $code = $info['code'];
}

```

### 启动服务

```shell
php artisan hprose:socket_server
```
**更新了路由后需要重新启动服务**


