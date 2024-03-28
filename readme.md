# LitePHP
LitePHP, A web and shell development framework.

So lite, so pure !

## 使用

### 入口文件

- #### 场景1：下载框架文件

```php
// 载入web框架核心类
include '../vendor/litephp/LiteWeb.php';
// 单例模式，调用启动方法。参数：(入口文件-相对于网站根目录,项目绝对路径,应用名)
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
    // 单例模式，调用启动方法。参数：(入口文件-相对于网站根目录,项目绝对路径,应用名)
    (LiteWeb::instance()->start('index.php', __DIR__ . '/../application', 'index'));
    ```

### 配置

- #### 配置说明

    ```
    mode: 运行模式
    - DEBUG: 开发模式，输出错误信息，记录错误+异常日志
    - Log: 日志（线上）模式，不输出任何错误异常信息，但记录到日志
    timezone: 时区, Asia/Shanghai
    log_path: 日志目录，默认项目目录下的logs文件夹
    include_files: 用户自定义载入文件列表, 绝对路径
    func_exception: 用户自定义异常处理函数 参数：($e, MODE)
    func_404: 用户自定义404回调函数
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

    - 支持嵌套路由，即路由组，该模式不支持正则
    - 请求方法 `method` : 优先级 `GET|POST > *`
    - 路由规则 `rule` : 支持URI严格匹配模式 + 正则匹配模式。正则模式要求符合 `preg_match` 函数传参
    - 执行方法 `function` : 支持数组或回调函数。数组0标必须是类,1标必须是方法名
    - 正则模式开关 `reg` : 是否启用正则，`true | false`。

    ```php
    [
        #非路由组格式：路由规则 => [请求方法,执行方法,正则模式开关],...
        'rule'  =>  ['method', 'function', ?'true,false'],...
        #路由组格式：父路由 => [ 子路由1 => [ 请求方法, 执行方法 ], 子路由2 => [ 请求方法, 执行方法 ]]
        'parent'    =>  ['child1'   =>  ['method','function'], 'child2' =>  ['method','function'],...]
        ...
    ]
    ```

- #### 路由示例

    ```php
    return [
        'index' => [
            'hello' =>  [
                'get|post|*',function(){
                    return 'index hello.';
                }
            ],
        ],
        '/hello' =>  [
            [
                'get',function(){
                    return 'get hello.';
                }
            ],
            [
                'post',function(){
                    return 'post hello.';
                }
            ]
        ],
        '#/index/+#' =>  [
            'get|post|*',function(){
                return 'reg.';
            },
            true
        ],
    ];
    ```

### Nginx配置

```nginx
server {
    listen       80;
    server_name  example.website;
    root   d://wwwroot/{project_name}/www;
    index  index.html index.htm index.php;

    location / {
       try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
}
```

## 框架目录树

┌&emsp;litephp 框架核心目录

│&emsp;├&emsp;includes 框架载入文件目录，如函数库

│&emsp;│&emsp;├ functions.php 框架核心函数

│&emsp;│&emsp;└ ... 其他框架载入文件

│&emsp;├&emsp;lib 框架核心类

│&emsp;│&emsp;├ DbDao.php DAO数据库操作

│&emsp;│&emsp;├ ExitException.php 异常形式输出到页面

│&emsp;│&emsp;├ Log.php 日志类

│&emsp;│&emsp;├ Pdo.php 数据库PDO类

│&emsp;│&emsp;├ Request.php 请求操作类

│&emsp;│&emsp;├ Response.php 响应类

│&emsp;│&emsp;├ Router.php 路由类

│&emsp;│&emsp;├ Session.php Session类

│&emsp;│&emsp;├ Template.php 轻量模板类

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

## 推荐项目部署目录树

┌&emsp;Application 项目目录

│&emsp;&emsp;├ index 前台应用目录

│&emsp;&emsp;│&emsp;&emsp;├ controller 应用控制器

│&emsp;&emsp;│&emsp;&emsp;├ logic 应用逻辑类

│&emsp;&emsp;│&emsp;&emsp;├ config.php 应用配置文件

│&emsp;&emsp;│&emsp;&emsp;├ routes.php 应用路由规则文件

│&emsp;&emsp;│&emsp;&emsp;├ functions.php 应用函数库文件

│&emsp;&emsp;│&emsp;&emsp;└ ... 其他应用文件

│&emsp;&emsp;├ logs 项目日志目录，记录日志等

│&emsp;&emsp;├ config.php 项目配置文件

│&emsp;&emsp;├ routes.php 项目路由规则文件

│&emsp;&emsp;├ functions.php 项目函数库文件

│&emsp;&emsp;└ ... 其他项目文件

├&emsp;vendor 依赖目录，使用composer命令执行生成

│&emsp;&emsp;├ litephp LitePHP框架

│&emsp;&emsp;└ ... 其他依赖

└&emsp;www 网站根目录

&emsp;&emsp;├ index.php 前台应用入口文件

&emsp;&emsp;└ ... 其他应用入口文件
