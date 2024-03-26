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
    private $Pdo;

    /**
     * PDOStatement 实例
     * @var \PDOStatement
     */
    private $PdoStatement;

    /**
     * 数据类型简称
     */
    private const FETCH_STYLE = [
        'ARRAY'     =>  \PDO::FETCH_ASSOC,
        'NUM'       =>  \PDO::FETCH_NUM,
        'OBJECT'    =>  \PDO::FETCH_OBJ,
        'BOTH'      =>  \PDO::FETCH_BOTH,
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
        $this->Pdo = new \PDO($dsnConfig, $username??NULL, $password??NULL, $options??NULL);
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
        $this->PdoPrepare($sql)->PdoStatementExecute($bindValue, $dataType);
        return $this->PdoStatement->rowCount();
    }

    /**
     * 当前插入的自增ID
     * INSERT
     * @return int
     */
    public function lastInsertId()
    {
        return $this->Pdo->lastInsertId();
    }

    /**
     * 查询 SQL 语句
     * SELECT
     * @param string $sql
     * @param array $bindValue
     * @param array $dataType
     * @param string $fetchStyle
     */
    public function query($sql, $bindValue = [], $dataType = [], $fetchStyle = 'array')
    {
        $this->PdoPrepare($sql)->PdoStatementExecute($bindValue, $dataType);
        return $this->PdoStatement->fetchAll(self::FETCH_STYLE[strtoupper($fetchStyle)] ?? NULL);
    }

    /**
     * 获取一条结果
     * @param string $sql
     * @param array $bindValue
     * @param array $dataType
     * @param string $fetchStyle
     */
    public function one($sql, $bindValue = [], $dataType = [], $fetchStyle = 'array')
    {
        $this->PdoPrepare($sql)->PdoStatementExecute($bindValue, $dataType);
        return $this->PdoStatement->fetch(self::FETCH_STYLE[strtoupper($fetchStyle)] ?? NULL);
    }

    /**
     * 开启事务
     * @return self $this
     */
    public function beginTransaction()
    {
        if (!$this->Pdo->inTransaction()) {
            return $this->Pdo->beginTransaction();
        }
        return $this;
    }

    /**
     * 提交事务
     * @return self $this
     */
    public function commit()
    {
        if ($this->Pdo->inTransaction()) {
            return $this->Pdo->commit();
        }
        return $this;
    }

    /**
     * 回滚事务
     * @return self $this
     */
    public function rollback()
    {
        if ($this->Pdo->inTransaction()) {
            return $this->Pdo->rollBack();
        }
        return $this;
    }

    /**
     * 获取debug信息 - 直接输出
     */
    public function debugDumpParams()
    {
        if ($this->PdoStatement) {
            $this->PdoStatement->debugDumpParams();
        }
    }

    /**
     * PDO 预处理
     * 获得 PDOStatement 语句实例
     * @param string $sql
     * @return self $this
     */
    private function PdoPrepare($sql)
    {
        try {
            if (!$this->Pdo || !($this->Pdo instanceof \PDO)) {
                throw new \ErrorException('Pdo not an PDO objcect.');
            }
            $PdoStatement = $this->Pdo->prepare($sql);
            if ($PdoStatement === FALSE) {
                throw new \ErrorException('PDO prepare failed.');
            }
            $this->PdoStatement = $PdoStatement;
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
    private function PdoStatementExecute($bindValue = [], $dataType = [])
    {
        if ($bindValue) {
            $dataTypeMap = ['num' => \PDO::PARAM_INT, 'str' => \PDO::PARAM_STR, 'bool' => \PDO::PARAM_BOOL, 'null' => \PDO::PARAM_NULL];
            foreach ($bindValue as $k => $v) {
                if (array_key_exists($k, $dataType)) {
                    $kType = $dataTypeMap[strtolower($dataType[$k])] ?? NULL;
                } else {
                    if (is_int($v) || is_float($v)) {
                        $kType = $dataTypeMap['num'];
                    } elseif (is_string($v)) {
                        $kType = $dataTypeMap['str'];
                    } elseif (is_bool($v)) {
                        $kType = $dataTypeMap['bool'];
                    } elseif (is_null($v)) {
                        $kType = $dataTypeMap['null'];
                    } else {
                        $kType = \PDO::PARAM_STR;
                    }
                }
                if (is_int($k)) {
                    $bindRes = $this->PdoStatement->bindValue($k + 1, $v, $kType);
                }
                if (is_string($k)) {
                    $bindRes = $this->PdoStatement->bindValue($k, $v, $kType);
                }
                if ($bindRes === FALSE) {
                    $errorInfo = $this->PdoStatement->errorInfo();
                    throw new \ErrorException("PDOStatement bindValue failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
                }
            }
        }
        if ($this->PdoStatement->execute() === FALSE) {
            $errorInfo = $this->PdoStatement->errorInfo();
            throw new \ErrorException("PDOStatement execute failed. [SQLSTATE] {$errorInfo[0]};[errCode] {$errorInfo[1]};[errInfo] {$errorInfo[2]}");
        }
        return $this;
    }
}
