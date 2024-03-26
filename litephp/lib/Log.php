<?php
/**
 * 日志类
 */
namespace litephp;

class Log
{
    use traits\instance;

    /**
     * 日志记录目录
     */
    protected $LOG_PATH;

    /**
     * 配置参数
     * @return self
     */
    public function config($LOG_PATH)
    {
        if (!is_dir($LOG_PATH) && (!mkdir($LOG_PATH, 0775, true) || !chmod($LOG_PATH, 0775))) {
            throw new \Exception('日志目录不存在，或检查目录读写权限。');
        }
        $this->LOG_PATH = $LOG_PATH;
        return $this;
    }

    /**
     * 记录日志
     */
    public function write($filename, $content, $flags = 0)
    {
        file_put_contents($this->LOG_PATH . '/' . $filename, $content, $flags);
    }

    /**
     * 记录日志
     * 内容追加和换行
     */
    public function writeLn($filename, $content)
    {
        $this->write($filename, $content. "\r\n", FILE_APPEND);
    }
}