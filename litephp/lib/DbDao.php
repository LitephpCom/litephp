<?php

/**
 * 数据库DAO操作类
 */

namespace litephp;

use Exception;
use stdClass;

class DbDao
{
    use traits\instance;

    /**
     * 所有查询SQL
     * '别名' => ['SQL',['条件1'=> '',...]]
     */
    protected $query = [];

    /**
     * 所有执行SQL
     * '别名' => ['SQL',['条件1'=> '',...]]
     */
    protected $exec = [];

    /**
     * 当前数据库操作类
     * @var Pdo
     */
    protected $PDO;

    /**
     * 初始化数据库操作类
     */
    public function setPDO($PDO)
    {
        $this->PDO = $PDO;
    }

    /**
     * 获取列表
     */
    public function select($command, $bind = [], $fetchMode = 'array', $filterFields = '')
    {
        $SQL = $this->query[$command] ?? '';
        if (!$SQL) {
            throw new Exception('DAO指令错误');
        }
        $fetchMode = strtoupper($fetchMode);
        $parse = $this->parseSQL($SQL, $bind);
        $result = $this->PDO->query($parse['SQL'], $parse['BIND'], $parse['DATA_TYPE'], $fetchMode);
        if ($result) {
            if (!$filterFields) {
                return $result;
            }
            if ($fetchMode == 'Array' || $fetchMode == 'OBJ') {
                $fields = explode(',', $filterFields);
                foreach ($result as &$row) {
                    foreach ($row as $key => $val) {
                        if (is_object($row) && !in_array($key, $fields)) {
                            unset($row->$key);
                        }
                        if (is_array($row) && !in_array($key, $fields)) {
                            unset($row[$key]);
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取单条记录
     */
    public function fetch($command, $bind = [], $fetchMode = 'array', $filterFields = '')
    {
        $SQL = $this->query[$command] ?? '';
        if (!$SQL) {
            throw new Exception('DAO指令错误');
        }
        $fetchMode = strtoupper($fetchMode);
        $parse = $this->parseSQL($SQL, $bind);
        $row = $this->PDO->fetch($parse['SQL'], $parse['BIND'], $parse['DATA_TYPE'], $fetchMode);
        if ($row) {
            if (!$filterFields) {
                return $row;
            }
            if ($fetchMode == 'Array' || $fetchMode == 'OBJ') {
                $fields = explode(',', $filterFields);
                foreach ($row as $key => $val) {
                    if (is_object($row) && !in_array($key, $fields)) {
                        unset($row->$key);
                    }
                    if (is_array($row) && !in_array($key, $fields)) {
                        unset($row[$key]);
                    }
                }
            }
        }
        return $row;
    }

    /**
     * 执行
     */
    public function execute($command, $bind = [])
    {
        $SQL = $this->query[$command] ?? '';
        if (!$SQL) {
            throw new Exception('DAO指令错误');
        }
        $parse = $this->parseSQL($SQL, $bind);
        return $this->PDO->execute($parse['SQL'], $parse['BIND'], $parse['DATA_TYPE']);
    }

    /**
     * 获取自增ID
     */
    public function lastInsertId()
    {
        return $this->PDO->lastInsertId();
    }

    /**
     * 开启事务
     */
    public function startTrans()
    {
        $this->PDO->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->PDO->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->PDO->rollback();
    }

    /**
     * 解析SQL及传参
     * 传参类型支持：默认模式、SQL、REPLACE、IN
     * $sql = select * from xxx where tag = :tag and {sqlTag} and {replaceTag1} and {replaceTag2} and in_id in ({inTag1}) and in_pid in ({inTag2});
     * $bind = ['tag'=>'tag','sqlTag'=>['SQL','sql string'],'replaceTag1'=>['REPLACE','replace string'],'replaceTag2'=>['REPLACE','replace val','INT'],'inTag1'=>['IN','1,2,3'],'inTag2'=>['IN'=>[1,2,3]]];
     * 注意：SQL和IN 类型容易出现SQL注入，请谨慎使用
     * @return array
     */
    private static function parseSQL($sql = '', $bind = [])
    {
        $bindVal = [];
        $dataType = [];
        # 优先解析SQL类型传参+组装绑定数组
        foreach ($bind as $tag => $val) {
            if (is_array($val)) {
                $operator = strtoupper(array_keys($val)[0]);
                $val2 = array_values($val)[0];
                if ($operator == 'SQL') {
                    # SQL 类型
                    if (!is_string($val2)) {
                        throw new Exception('解析SQL错误：SQL类型传参，只允许字符串格式传值。');
                    }
                    $sql = str_replace('{' . $tag . '}', ' ' . $val2 . ' ', $sql);
                }
            }
        }
        # 再解析REPLACE和IN类型传参
        foreach ($bind as $tag => $val) {
            if (is_string($val)) {
                # 默认类型
                $bindVal[':' . $tag] = $val;
                $dataType[':' . $tag] = 'str';
            } elseif (is_array($val) && count($val) >= 2) {
                $operator = strtoupper($val[0]);
                if ($operator == 'REPLACE') {
                    # REPLACE 类型
                    if (!is_string($val[1])) {
                        throw new Exception('解析SQL错误：REPLACE类型传参，只允许字符串格式传值。');
                    }
                    $bindVal[':' . $tag] = $val;
                    $dataType[':' . $tag] = $val[2] ?? 'str';
                }
                if ($operator == 'IN') {
                    # IN 类型
                    if (is_array($val[1])) {
                        $val[1] = implode(',', $val[1]);
                    }
                    if (!is_string($val[1])) {
                        throw new Exception('解析SQL错误：IN类型操作符，只允许字符串或数组格式传值。');
                    }
                    $sql = str_replace('{' . $tag . '}', $val2, $sql);
                }
            } else {
                throw new Exception('SQL语句参数格式错误');
            }
        }
        return ['SQL' => $sql, 'BIND' => $bindVal, 'DATA_TYPE' => $dataType];
    }
}
