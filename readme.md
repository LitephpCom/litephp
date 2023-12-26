# LitePHP
LitePHP, A web and shell development framework.

## 使用

### 入口文件

- #### 场景1：下载框架文件

```php
// 载入web框架核心类
include '../vendor/litephp/LiteWeb.php';
// 单例模式，调用启动方法。参数：(入口文件,项目绝对路径,应用名)
(LiteWeb::instance()->start('index.php', __DIR__ . '/../application', 'index'));
```

- #### 场景2： composer方式引入

  - ##### 1. composer

    ```shell
    composer require LitephpCom\litephp
    ```

   - ##### 2. 载入

    ```php
    // 载入 composer 自动加载文件
    include '../vendor/autoload.php';
    // 单例模式，调用启动方法。参数：(入口文件,项目绝对路径,应用名)
    (LiteWeb::instance()->start('index.php', __DIR__ . '/../application', 'index'));
    ```

### 配置

- #### 配置说明

    ```
    mode: 运行模式
    - Dev: 开发模式，输出错误信息，记录错误+异常日志
    - Log: 日志（线上）模式，不输出任何错误异常信息，但记录到日志
    timezone: 时区, Asia/Shanghai
    runtime: 运行时目录，默认项目目录下的runtime文件夹
    functions: 官方函数库文件名列表, /functions 目录下除litephp.php文件的其他文件名
    includes: 用户自定义载入文件列表, 绝对路径
    exception: 用户自定义异常处理函数 参数：($e, MODE)
    function404: 用户自定义404回调函数
    ```

- #### 配置示例

    ```php
    <?
    return [
        'mode'      =>  'log',
        'timezone'  =>  'Asia/Shanghai',
    ];
    ```


### 路由

- #### 路由说明

    - 请求方法 `method` : 优先级 `GET|POST > *`
    - 路由规则 `rule` : 支持严格URI匹配模式 + 正则匹配模式。正则模式要求符合 `preg_match` 函数传参
    - 执行方法 `function` : 支持数组或回调函数。数组0标必须是类,1标必须是方法名
    - 正则模式开关 `reg` : 是否启用正则，`true | false`。

    ```php
    [
        # 请求方法=> 路由规则, 执行方法, 正则模式开关
        ['method'   =>  'rule', 'function', 'true|false'],
        ...
    ]
    ```

- #### 路由示例

    ```php
    return [
        ['*' => '/index/hello', ['\index\controller\index', 'index'], false],
        ['get' => '/index', function () {
            return 'hello world';
        }, false],
        ['get' => '#/index/*#', function() {
            return 'reg ok.';
        }, true],
    ];
    ```

## 框架目录树

┌&emsp;litephp 框架核心目录
│&emsp;├&emsp;functions 框架函数库
│&emsp;│&emsp;├ litephp.php 框架核心函数
│&emsp;│&emsp;└ ... 其他函数库
│&emsp;├&emsp;lib 框架核心类
│&emsp;│&emsp;├ ExitException.php 异常形式输出到页面
│&emsp;│&emsp;├ Log.php 日志类
│&emsp;│&emsp;├ Response.php 响应类
│&emsp;│&emsp;├ Router.php 路由类
│&emsp;│&emsp;└ ... 其他类库
│&emsp;├&emsp;tpl 框架模板文件
│&emsp;│&emsp;├ 404.html.php 404 错误模板
│&emsp;│&emsp;├ exception.html.php 服务异常模板
│&emsp;│&emsp;└ ... 其他模板
│&emsp;└&emsp;traits trait文件
│&emsp;&emsp;&emsp;├ instance.php 单例模式
│&emsp;&emsp;&emsp;└ ... 其他trait
├&emsp;LiteWeb.php web框架核心类，用于入口文件引入
├&emsp;LiteShell.php 命令行框架核心类，用于shell脚本执行
└&emsp;readme.md 框架说明文件

## 推荐部署目录树

┌&emsp;Application 项目目录
│&emsp;&emsp;├ index 前台应用目录
│&emsp;&emsp;│&emsp;&emsp;├ controller 应用控制器
│&emsp;&emsp;│&emsp;&emsp;├ logic 应用逻辑类
│&emsp;&emsp;│&emsp;&emsp;├ config.php 应用配置文件
│&emsp;&emsp;│&emsp;&emsp;├ routes.php 应用路由规则文件
│&emsp;&emsp;│&emsp;&emsp;└ ... 其他应用文件
│&emsp;&emsp;├ runtime 项目运行时目录，记录日志等
│&emsp;&emsp;├ config.php 项目配置文件
│&emsp;&emsp;├ routes.php 项目路由规则文件
│&emsp;&emsp;└ ... 其他项目文件
├&emsp;vendor 依赖目录，使用composer命令执行生成
│&emsp;&emsp;├ litephp LitePHP框架
│&emsp;&emsp;└ ... 其他依赖
└&emsp;www 网站根目录
&emsp;&emsp;├ index.php 前台应用入口文件
&emsp;&emsp;└ ... 其他应用入口文件