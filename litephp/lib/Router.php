<?php

/**
 * 路由类
 */

namespace litephp;

class Router
{
    use traits\instance;

    /**
     * 入口文件
     */
    private $indexfile = 'index.php';

    /**
     * 路由表
     * 格式: 请求方法 => [路由规则:严格匹配或正则, 正则模式:是或否, 执行方法: 回调函数]
     * 'GET|POST|ANY' => [rule:'url|reg',reg:true:false,callback: func]
     */
    private $maps = [];

    /**
     * 当前请求方法
     */
    public $method;

    /**
     * 当前请求URI信息
     */
    public $uri;

    /**
     * 解析后的pathinfo信息
     */
    public $path;

    /**
     * 路由解析结果
     */
    private $matched = false;

    /**
     * 解析成功的路由规则
     */
    public $route;

    /**
     * 当前解析执行结果
     */
    public $response;

    /**
     * 解析失败时的处理函数
     */
    private $func404;

    /**
     * 支持的请求方法
     */
    private $_methods = ['GET','POST','HEAD','PUT','DELETE','OPTIONS','TRACE','PATCH','*'];

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
    }

    /**
     * 配置
     * @return self
     */
    public function init($indexfile = 'index.php', $func404 = null)
    {
        $this->indexfile = $indexfile;
        if ($func404 && !LiteCheckFunction($func404)) {
            throw new \Exception('路由错误: 配置404函数错误。');
        }
        $this->func404 = $func404;
        //处理pathinfo
        $pos = strpos($this->uri, '?');
        $path = $pos === FALSE ? strtolower($this->uri) : strtolower(substr($this->uri, 0, $pos));
        if (stripos($this->uri, '/' . strtolower($this->indexfile)) === 0) {
            $this->path = substr($path, strlen('/' . $this->indexfile));
        } else {
            $this->path = $path;
        }
        return $this;
    }

    /**
     * 单独设置404函数
     * @return self
     */
    public function set404($func404)
    {
        if (!LiteCheckFunction($func404)) {
            throw new \Exception('路由错误: 404函数不可用。');
        }
        $this->func404 = $func404;
        return $this;
    }

    /**
     * 单独新增1条路由
     * @return self
     */
    public function push($method, $rule, $func, $reg = false, $route = [])
    {
        if (!LiteCheckFunction($func)) {
            throw new \Exception('路由错误: 新增处理函数不可用。[' . $method . ' => ' . $rule . ']');
        }
        $method = strtoupper($method);
        if (!in_array($method, $this->_methods)) {
            throw new \Exception('路由错误: 请求方法不支持。[' . $method . ' => ' . $rule . ']');
        }
        if (!isset($this->maps[$method])) $this->maps[$method] = [];
        array_push($this->maps[$method], ['rule' => strtolower($rule), 'reg' => $reg ? true : false, 'function' => $func, 'self' => $route]);
        return $this;
    }

    /**
     * 批量增加路由规则
     * 非路由组格式：rule => [method,function,reg],...
     * 路由组格式：rule0 => [ rule1 => [ method, function ]]
     * [
     *     'path1' => [
     *         'path2' =>  [
     *             'path3' =>  [
     *                 'get|post',function(){}
     *             ],
     *         ],
     *     ],
     *     'path4' =>  [
     *         ['get',function(){}],
     *         ['post',function(){}],
     *     ],
     *     'path5'  =>  [
     *         '*', function(){}
     *     ],
     *     '#path6#' =>  [
     *         'get|post',function(){},true
     *     ],
     * ];
     */
    public function pushAll($maps = [], $parent_rule = '')
    {
        foreach($maps as $rule => $route) {
            if (!is_array($route)) {
                throw new \Exception('路由规则错误: 格式错误。规则：'.$rule);
            }
            $key = LiteArrayKeyFirst($route);
            if (is_int($key)) {
                # 非嵌套路由
                if (is_string($route[0])) {
                    # 单请求方法规则路由
                    $methods = $this->parseMethod($route[0]);
                    if (!$methods) {
                        throw new \Exception('路由规则错误: 请求方法错误。规则：'.$rule);
                    }
                    if (!isset($route[1]) || !LiteCheckFunction($route[1])) {
                        throw new \Exception('路由规则错误: 方法无非调用。规则：'.$rule);
                    }
                    # 正则判断
                    $is_reg = isset($route[2]) && $route[2] === true ? true : false;
                    $rule = $is_reg ? $rule : $parent_rule . '/' . trim(strtolower($rule),'/');
                    foreach($methods as $method) {
                        $this->push($method, $rule, $route[1], $is_reg, $route);
                    }
                } elseif (is_array($route[0])) {
                    # 多请求方法规则路由
                    foreach($route as $subRoute) {
                        $subKey = LiteArrayKeyFirst($subRoute);
                        if (!is_int($subKey)) {
                            throw new \Exception('路由规则错误: 同路由多请求方法规则格式错误。规则：'.$rule);
                        }
                        $methods = $this->parseMethod($subRoute[0]);
                        if (!$methods) {
                            throw new \Exception('路由规则错误: 请求方法错误。规则：'.$rule);
                        }
                        if (!isset($subRoute[1]) || !LiteCheckFunction($subRoute[1])) {
                            throw new \Exception('路由规则错误: 方法无非调用。规则：'.$rule);
                        }
                        # 正则判断
                        $is_reg = isset($subRoute[2]) && $subRoute[2] === true ? true : false;
                        $rule = $is_reg ? $rule : $parent_rule . '/' . trim(strtolower($rule),'/');
                        foreach($methods as $method) {
                            $this->push($method, $rule, $subRoute[1], $is_reg, $subRoute);
                        }
                    }
                } else {
                    throw new \Exception('路由规则错误: 配置格式错误。规则：'.$rule);
                }
            } else {
                # 嵌套路由
                $rule = $parent_rule . '/' . trim(strtolower($rule),'/');
                $this->pushAll($route, $rule);
            }
        }
    }
    
    /**
     * 是否匹配到路由
     * @return bool
     */
    public function isMatched()
    {
        return !!$this->matched;
    }

    /**
     * 路由解析
     * 1. 优先解析GET,POST; 再尝试*
     * 2. 按路由配置顺序匹配
     * 3. 匹配 * 方法的规则
     * 4. 匹配 * 方法的 * 规则
     * 5. 以上无法匹配，则解析失败 404
     */
    public function parse()
    {
        ob_clean();
        ob_start();
        // 匹配具体的请求方法
        if (array_key_exists($this->method, $this->maps)) {
            foreach ($this->maps[$this->method] as $route) {
                //严格模式
                if (!$route['reg'] && $route['rule'] === $this->path) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
                //正则模式
                if ($route['reg'] && preg_match($route['rule'], $this->path, $matches)) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
            }
        }

        // 匹配 任何* 方法
        $any = [];
        if (!$this->matched && array_key_exists('*', $this->maps)) {
            foreach ($this->maps['*'] as $route) {
                //跳过，放最后匹配
                if ($route['rule'] === '*') {
                    $any = $route;
                    continue;
                }
                //严格模式
                if (!$route['reg'] && $route['rule'] === $this->path) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
                //正则模式
                if ($route['reg'] && preg_match($route['rule'], $this->path, $matches)) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
            }
        }

        // 匹配 * 方法的 * 规则
        if (!$this->matched && $any) {
            $this->matched = true;
            $this->route = $any;
            if (is_array($any['function'])) {
                $this->response = call_user_func([new $any['function'][0], $any['function'][1]]);
            } else {
                $this->response = call_user_func($any['function']);
            }
        }

        // 解析失败，404
        if (!$this->matched && LiteCheckFunction($this->func404)) {
            $this->response = call_user_func($this->func404);
        }
        $flushContent = ob_get_contents();
        ob_end_clean();
        if ($flushContent) {
            $this->response = $flushContent . (string)$this->response;
        }
        return $this->response;
    }

    /**
     * 检测支持的方法
     * @return bool|array
     */
    private function parseMethod($method)
    {
        $methods = explode('|', $method);
        $return = [];
        foreach($methods as $_method) {
            $_method = strtoupper($_method);
            if (!in_array($_method, $this->_methods)) {
                return false;
            }
            $return[] = $_method;
        }
        return $return;
    }
}
