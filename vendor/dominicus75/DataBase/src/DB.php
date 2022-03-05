<?php

namespace Dominicus75\DataBase;

use \Dominicus75\Config\Config;
use \ArrayAccess;
use \PDO;


class DB extends PDO
{

    /**
     *
     * @var array default pdo options
     *
     */
    protected static $options  = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    /**
     *
     * @var array list of pdo drivers what compatibile this implementation
     *
     */
    const DRIVERS = ['mysql'];

    /**
     *
     * @var \Dominicus75\DataBase\DB current pdo instance
     *
     */
    protected static ?self $instance = null;

    /**
     *
     * @var string name of current pdo driver
     *
     */
    private string $driver;

    /**
     *
     * @var string name of current database
     *
     */
    private string $name;

    /**
     *
     * @var array List of tables what belongs to this database
     *
     */
    private array $tables;


    /**
     *
     * @param \Dominicus75\Config\Config config object, what implements
     * \ArrayAccess interface
     * @see https://www.php.net/manual/en/class.arrayaccess.php
     *
     * @throws \PDOException
     *
     */
    private function __construct(ArrayAccess $config) {

        try {

            self::$instance = parent::__construct(
                $config->offsetGet('datasource'),
                $config->offsetGet('username'),
                $config->offsetGet('password'),
                self::setOptions($config->offsetGet('options'))
            );

        } catch(\PDOException $e) { throw $e; }

    }

    /**
     * Set options to PDO (parent) class constructor
     *
     * @param array|null $options
     * @return array|null
     *
     */
    private static function setOptions(?array $options): ?array {
        return is_null($options) ? self::$options : null;
    }

    /**
     *
     * @param \Dominicus75\Config\Config object, what implements
     * \ArrayAccess interface
     * @see https://www.php.net/manual/en/class.arrayaccess.php
     * @return self a singleton instance of this class
     *
     * @throws \PDOException
     *
     */
    public static function getInstance(ArrayAccess $config): self {

        if (is_null(self::$instance)) {

            try {
                self::$instance = new self($config);
                self::$instance->exec('set names utf8');
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setDriver();
                self::$instance->setName();
                self::$instance->setTables();
            } catch(\PDOException $e) { throw $e; }

        }

        return self::$instance;

    }

    /**
     * Setting of current pdo driver's name
     *
     * @return self
     *
     */
    private function setDriver(): self {

        $driver = parent::getAttribute(PDO::ATTR_DRIVER_NAME);

        if(in_array($driver, self::DRIVERS)) {
            $this->driver = $driver;
            return $this;
        } else {
            throw new \PDOException($driver. 'is not supported');
        }

    }

    /**
     * @return self
     */
    private function setName(): self {

        switch($this->driver) {
            case 'mysql':
            $sql = "select database()";
            break;
        }

        $statement = self::$instance->query($sql);

        if($statement->execute()) {
            if($result = $statement->fetchColumn()) {
                $this->name = $result;
            } else {
                throw new \PDOException('Given database not found in this server');
            }
            return $this;
        } else {
            throw new \PDOException('PDOStatement::execute() function returned with false');
        }

    }

    /**
     * Querying of tables list from database
     *
     * @return self
     * @throws \PDOException if PDOStatement::fetchAll() or execute() returns with false
     *
     */
    private function setTables(): self {

        switch($this->driver) {
            case 'mysql':
            $sql = "SHOW TABLES";
            break;
        }

        $statement = self::$instance->query($sql);

        if($statement->execute()) {
            if($tables = $statement->fetchAll(PDO::FETCH_COLUMN)) {
                $this->tables = $tables;
            } else {
                $this->tables = [];
            }
            return $this;
        } else {
            throw new \PDOException('PDOStatement::execute() function returned with false');
        }

    }

    /**
     * @return string the name of current database
     */
    public function getName(): string { return $this->name; }

    /**
     * @return string name of current pdo driver
     */
    public function getDriver(): string { return $this->driver; }

    /**
     *
     * @param string $table Name of the searched table
     * @return bool Returns true if table is found in this database, false otherwise
     *
     */
    public function hasTable(string $table): bool { return in_array($table, $this->tables); }

    /**
     *
     * @return array List of tables what belongs to this database
     *
     */
    public function getTables(): array { return $this->tables; }

}
