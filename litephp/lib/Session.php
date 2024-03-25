<?php

/**
 * Session 操作类
 */

namespace litephp;

class Session
{
    use traits\Instance;

    /**
     * 前缀
     * @var string
     */
    private $_prefix = '_LitePHP_';

    /**
     * 设置前缀
     * @return self
     */
    public function setPrefix($prefix = '')
    {
        $prefix and $this->_prefix = $prefix;
        return $this;
    }

    /**
     * 启用 Session
     * @return self
     */
    public function start($options = [])
    {
        if (PHP_SESSION_ACTIVE != session_status() && !session_start($options ?: NULL)) {
            throw new \ErrorException('Session unable started.');
        }
        return $this;
    }

    /**
     * 获取 Session 值
     */
    public function get($name, $prefix = NULL)
    {
        $this->start();
        if (is_null($prefix)) $prefix = $this->_prefix;
        return $_SESSION[$prefix][$name] ?? NULL;
    }

    /**
     * 获取全部 Session
     */
    public function ALL($prefix = NULL)
    {
        $this->start();
        return $_SESSION[$prefix ?: $this->_prefix] ?? NULL;
    }

    /**
     * 设置 Session 值
     * @return self $this
     */
    public function SET($name, $value = NULL, $prefix = NULL)
    {
        $this->start();
        if (is_null($prefix)) $prefix = $this->_prefix;
        if (!$name) {
            throw new \ErrorException('Session Name is an illegal value.');
        }
        $_SESSION[$prefix][$name] = $value;
        return $this;
    }

    /**
     * 删除 Session 值
     * @return self $this
     */
    public function DEL($name, $prefix = NULL)
    {
        $this->start();
        if (is_null($prefix)) $prefix = $this->_prefix;
        unset($_SESSION[$prefix][$name]);
        return $this;
    }

    /**
     * 删除所有 Session
     * @return self $this
     */
    public function DEL_ALL($prefix = NULL)
    {
        $this->start();
        !$prefix and $prefix = $this->_prefix;
        unset($_SESSION[$prefix]);
        return $this;
    }

    /**
     * 销毁 Session
     * @param bool $destroyCookie
     * @return bool
     */
    public function DESTROY($destroyCookie = FALSE)
    {
        if ($_SESSION) {
            /**
             * !!! don't use `unset($_SESSION)`,look php Doc 'session_unset()'
             * 警告 请不要使用unset($_SESSION)来释放整个$_SESSION， 因为它将会禁用通过全局$_SESSION去注册会话变量
             */
            $_SESSION = [];
        }
        if ($destroyCookie) {
            // 如果要清理的更彻底，那么同时删除会话 cookie
            // 注意：这样不但销毁了会话中的数据，还同时销毁了会话本身
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
        }
        session_unset(); //清除内存$_SESSION的值，不删除会话文件及会话ID
        return session_destroy(); //销毁会话数据，删除会话文件及会话ID，但内存$_SESSION变量依然保留
    }
}
