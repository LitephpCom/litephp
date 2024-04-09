<?php

/**
 * .ini 文件解析
 * INI 文件的基本格式如下：
 *  ```
 *  ; 这是注释
 *  [section1]
 *  key1 = value1
 *  key2 = value2
 *  [section2]
 *  key3 = value3
 *  key4 = value4
 *  ```
 *  在这个示例中：
 *  - `;` 开头的行表示注释，不会被解析。
 *  - `[section]` 表示一个节（section），用来组织配置项。
 *  - `key = value` 表示一个配置项和对应的数值。
 */

namespace litephp\core;

class LiteINI
{
    /**
     * 解析配置内容
     */
    public static function parse($content = '')
    {
        $result = [];
        if (!$content) {
            return $result;
        }
        $contentLines = explode(PHP_EOL, $content);
        $lastSection = '';
        foreach ($contentLines as $lines) {
            $lines = trim($lines);
            # 空行,注释
            if (!$lines || strpos($lines, ';') !== false) {
                continue;
            }
            # 节点
            if (preg_match('/\[(.*?)\]/', $lines, $matches)) {
                if ($lastSection != $matches[1]) {
                    $lastSection = $matches[1];
                }
                continue;
            }
            $lines = explode('=', $lines);
            if ($lastSection) {
                # 存在节点
                $result[$lastSection][trim($lines[0])] = isset($lines[1]) ? str_replace("'", '', str_replace('"', '', trim($lines[1]))) : NULL;
            } else {
                # 不存在节点
                $result[trim($lines[0])] = isset($lines[1]) ? str_replace("'", '', str_replace('"', '', trim($lines[1]))) : NULL;
            }
        }
    }

    /**
     * 解析配置文件
     */
    public static function parseByFile($file)
    {
        if (!file_exists($file)) {
            throw new \ErrorException('文件不存在');
        }
        $content = file_get_contents($file);
        return self::parse($content);
    }
}
