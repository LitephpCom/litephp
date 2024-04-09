<?php

/**
 * 简单的.ENV文件处理
 * .env 文件的格式规范：
 *  1. **键值对格式**：每行包含一个键值对，键和值之间用等号（=）连接，例如：
 *  ```
 *  DB_HOST=localhost
 *  DB_USER=root
 *  DB_PASSWORD=secret
 *  ```
 *  2. **注释**：可以使用井号（#）或分号（;）开头表示注释，注释内容不会被解析，例如：
 *  ```
 *  # Database Configuration
 *  DB_NAME=mydatabase
 *  ```
 *  3. **空行和空格**：空行会被忽略，键和值之间可以有空格，但键和等号之间不应有空格，例如：
 *  ```
 *  API_KEY = myapikey  # 错误
 *  API_KEY=myapikey    # 正确
 *  ```
 *  4. **引号**：通常情况下，值可以使用引号括起来，特别是值中包含空格或特殊字符时，例如：
 *  ```
 *  APP_NAME="My Application"
 *  ```
 *  5. **换行符**：不同操作系统对换行符的处理不同，建议在 .env 文件中使用 Unix 风格的换行符（\n）。
 *  6. **敏感信息**：由于 .env 文件通常包含敏感信息，应该妥善保存，不应该公开或提交到版本控制系统中。
 */

namespace litephp\core;

class LiteEnv
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
        foreach ($contentLines as $lines) {
            $lines = trim($lines);
            # 空行,注释
            if (!$lines || strpos($lines, '#') !== false || strpos($lines, ';') !== false) {
                continue;
            }
            $lines = explode('=', $lines);
            $result[trim($lines[0])] = isset($lines[1]) ? str_replace("'", '', str_replace('"', '', trim($lines[1]))) : NULL;
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
