<?php

/**
 * mysql pdo 操作类
 */

namespace litephp;

class Pdo
{

    use traits\Instance;

    /**
     * PDO 连接实例
     * @var \PDO
     */
    private $PDO;

    /**
     * PDOStatement 实例
     * @var \PDOStatement
     */
    private $PDOStatement;

    /**
     * 常用数据类型映射
     */
    private const FETCH_MODE = [
        'ARRAY'     =>  \PDO::FETCH_ASSOC,
        'NUM'       =>  \PDO::FETCH_NUM,
        'OBJ'       =>  \PDO::FETCH_OBJ,
        'BOTH'      =>  \PDO::FETCH_BOTH,
    ];

    /**
     * 常用参数类型映射
     */
    private const PARAM_TYPE = [
        'BOOL'      =>  \PDO::PARAM_BOOL,
        'NULL'      =>  \PDO::PARAM_NULL,
        'INT'       =>  \PDO::PARAM_INT,
        'STR'       =>  \PDO::PARAM_STR,
        'LOB'       =>  \PDO::PARAM_LOB,
        'STMT'      =>  \PDO::PARAM_STMT,
        'INOUT'     =>  \PDO::PARAM_INPUT_OUTPUT,
    ];

    /**
     * 构造函数
     */
    public function __construct($dsnConfig, $username = NULL, $password = NULL, $options = NULL)
    {
        if (!$dsnConfig) {
            throw new \ErrorException('pdo dsn not empty.');
        }
        if (!is_string($dsnConfig) || !is_array($dsnConfig)) {
            throw new \ErrorException('pdo dsn data type error.');
        }
        if (is_array($dsnConfig)) {
            if (!isset($dsnConfig['host']) || !isset($dsnConfig['dbname'])) {
                throw new \ErrorException('pdo dsn config check failed.');
            }
            $port = isset($dsnConfig['port']) ? $dsnConfig['port'] : 3306;
            $charset = isset($dsnConfig['charset']) ? $dsnConfig['charset'] : 'utf8';
            $dsnConfig = "mysql:dbname={$dsnConfig['dbname']};host={$dsnConfig['host']};port={$port};charset={$charset}";
        }
        $this->PDO = new \PDO($dsnConfig, $username??NULL, $password??NULL, $options??NULL);
    }

    /**
     * 获得 PDO 实列
     * @return null|\PDO
     */
    public function fetchPDO()
    {
        return $this->PDO;
    }

    /**
     * 获得 PDOStatement 实例
     * @return null|\PDOStatement
     */
    public function fetchPDOStatement()
    {
        return $this->PDOStatement;
    }

    /**
     * 主动关闭连接
     */
    public function close()
    {
        $this->PDOStatement = null;
        $this->PDO = null;
    }

    /**
     * 执行 SQL 语句
     * UPDATE DELETE INSERT
     * @param string $sql
     * @param array $bindValue
     * @param array $dataType
     * @return int 影响行数
     */
    public function execute($sql, $bindValue = [], $dataType = [])
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->rowCount();
    }

    /**
     * 当前插入的自增ID
     * INSERT
     * @return int
     */
    public function lastInsertId()
    {
        return $this->PDO->lastInsertId();
    }

    /**
     * 查询 SQL 语句
     * SELECT
     * @param string $sql
     * @param array $bindValue
     * @param array $dataType
     * @param string $fetchMode
     */
    public function query($sql, $bindValue = [], $dataType = [], $fetchMode = 'array')
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->fetchAll(self::FETCH_MODE[strtoupper($fetchMode)] ?? NULL);
    }

    /**
     * 获取一条结果
     * @param string $sql
     * @param array $bindValue
     * @param array $dataType
     * @param string $fetchMode
     */
    public function fetch($sql, $bindValue = [], $dataType = [], $fetchMode = 'array')
    {
        $this->PDOPrepare($sql)->PDOStatementExecute($bindValue, $dataType);
        return $this->PDOStatement->fetch(self::FETCH_MODE[strtoupper($fetchMode)] ?? NULL);
    }

    /**
     * 开启事务
     * @return self $this
     */
    public function beginTransaction()
    {
        if (!$this->PDO->inTransaction()) {
            return $this->PDO->beginTransaction();
        }
        return $this;
    }

    /**
     * 提交事务
     * @return self $this
     */
    public function commit()
    {
        if ($this->PDO->inTransaction()) {
            return $this->PDO->commit();
        }
        return $this;
    }

    /**
     * 回滚事务
     * @return self $this
     */
    public function rollback()
    {
        if ($this->PDO->inTransaction()) {
            return $this->PDO->rollBack();
        }
        return $this;
    }

    /**
     * 获取debug信息 - 直接输出
     */
    public function debugDumpParams()
    {
        if ($this->PDOStatement) {
            $this->PDOStatement->debugDumpParams();
        }
    }

    /**
     * PDO 预处理
     * 获得 PDOStatement 语句实例
     * @param string $sql
     * @return self $this
     */
    private function PDOPrepare($sql)
    {
        try {
            if (!$this->PDO || !($this->PDO instanceof \PDO)) {
                throw new \ErrorException('Pdo not an PDO objcect.');
            }
            $PDOStatement = $this->PDO->prepare($sql);
            if ($PDOStatement === FALSE) {
                throw new \ErrorException('PDO prepare failed.');
            }
            $this->PDOStatement = $PDOStatement;
            return $this;
        } catch (\PDOException $e) {
            throw new \ErrorException($e->getMessage());
        }
    }

    /**
     * PDOStatement 语句对象执行
     * @param array $bindValue
     * @param array $dataType ['key'=>num|str|bool|null]
     * @return self $this
     */
    private function PDOStatementExecute($bindValue = [], $dataType = [])
    {
        if ($bindValue) {
            foreach ($bindValue as $k => $v) {
                if (array_key_exists($k, $dataType)) {
                    if (array_key_exists(strtoupper($dataType[$k]), self::PARAM_TYPE)) {
                        $kType = self::PARAM_TYPE[strtoupper($dataType[$k])];
                    } else {
                        $kType = $dataType[$k];
                    }
                } else {
                    if (is_int($v) || is_float($v)) {
                        $kType = self::PARAM_TYPE['NUM'];
                    } elseif (is_string($v)) {
                        $kType = self::PARAM_TYPE['STR'];
                    } elseif (is_bool($v)) {
                        $kType = self::PARAM_TYPE['BOOL'];
                    } elseif (is_null($v)) {
                        $kType = self::PARAM_TYPE['NULL'];
                    } else {
                        $kType = self::PARAM_TYPE['STR'];
                    }
                }
                if (is_int($k)) {
                    $bindRes = $this->PDOStatement->bindValue($k + 1, $v, $kType);
                }
                if (is_string($k)) {
                    $bindRes = $this->PDOStatement->bindValue($k, $v, $kType);
                }
                if ($bindRes === FALSE) {
                    $errorInfo = $this->PDOStatement->errorInfo();
                    throw new \ErrorException("PDOStatement bindValue failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
                }
            }
        }
        if ($this->PDOStatement->execute() === FALSE) {
            $errorInfo = $this->PDOStatement->errorInfo();
            throw new \ErrorException("PDOStatement execute failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
        }
        return $this;
    }
}
