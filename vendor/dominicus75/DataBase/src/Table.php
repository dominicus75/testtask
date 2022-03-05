<?php

/*
 * @package DataBase
 * @copyright 2020 Domokos Endre János <domokos.endrejanos@gmail.com>
 * @license MIT License (https://opensource.org/licenses/MIT)
 */

namespace Dominicus75\DataBase;

use \Dominicus75\Config\Config;
use \ArrayAccess;
use \PDO;

class Table
{

    /**
     *
     * @var \Dominicus75\DataBase\DB a singleton instance of DB
     *
     */
    protected DB $database;

    /**
     *
     * @var string name of the current table (e. g. 'pages' or 'users')
     *
     */
    protected string $name;

    /**
     *
     * @var array what contains the name of the primary key column (e. g. 'id')
     * and this column has auto_increment attribute or not in form
     * $primaryKey = ['name' => string, 'auto_increment' => bool]
     *
     */
    protected array $primaryKey;

    /**
     *
     * @var array list of columns, what belong to the current table
     *
     */
    protected array $columns;


    /**
     *
     * @param ArrayAccess|\Dominicus75\DataBase\DB $confOrInstance an instance of \Dominicus75\Config\Config
     * or an instance of \Dominicus75\DataBase\DB
     * @param string $table name of the current table
     * @throws \PDOException if
     * - $table is not found in this database
     * - current PDO driver is not supported (now only mysql supported yet)
     * - PDOStatement::fetchAll() or execute() returns with false
     *
     */
    public function __construct(ArrayAccess|\Dominicus75\DataBase\DB $confOrInstance, string $table){

        try {

            if($confOrInstance instanceof ArrayAccess) {
                $this->database = DB::getInstance($confOrInstance);
            } elseif($confOrInstance instanceof \Dominicus75\DataBase\DB) {
                $this->database = $confOrInstance;
            }

            if($this->database->hasTable($table)) {
                $this->name = $table;
            } else {
                throw new \PDOException($table. 'is not found in this database');
            }

            $this->setColumns();
            $this->setPrimaryKey();

        } catch(\PDOException $pdoe) { throw $pdoe; }

    }

    /**
    *
    * @return self 
    * @throws \PDOException
    * if this table is not found in this database
    * if PDOStatement::fetchAll() or execute() returns with false
    *
    */
    private function setColumns(): self {

        if($this->database->hasTable($this->name)) {

            switch($this->database->getDriver()) {
                case 'mysql':
                    $sql = "SHOW COLUMNS FROM `".$this->name."`";
                    break;
            }

            $statement = $this->database->query($sql);

            if($statement->execute()) {
                if($columns = $statement->fetchAll()) {
                    $result = [];
                    foreach($columns as $column) {
                        $type = (preg_match("/(char|text)/is", $column['Type'])) ? PDO::PARAM_STR : PDO::PARAM_INT;
                        $nullable = ($column['Field'] == 'YES') ? true : false;
                        $this->columns[$column['Field']] = [
                            'bind'     => ":".$column['Field'], 
                            'type'     => $type, 
                            'nullable' => $nullable
                        ];
                    }
                    return $this;
                } else {
                    throw new \PDOException('PDOStatement::fetchAll() function returned with false');
                }
            } else {
            throw new \PDOException('PDOStatement::execute() function returned with false');
            }

        } else {
            throw new \PDOException($this->name. 'is not found in '.$this->database->getName().' database');
        }

    }

  
    /**
     *
     * @return self
     * @throws \PDOException,
     * if this table has not a primary key,
     * if this table is not found in this database,
     * if PDOStatement::fetchAll() or execute() returns with false.
     *
     */
    private function setPrimaryKey(): self {

        if($this->database->hasTable($this->name)) {

            switch($this->database->getDriver()) {
                case 'mysql':
                    $sql = "SHOW COLUMNS FROM `".$this->name."`";
                    break;
            }

            $statement = $this->database->query($sql);

            if($statement->execute()) {
                if($columns = $statement->fetchAll()) { 
                    $index = 0;
                    while($index < count($columns)) { 
                        if($columns[$index]['Key'] == 'PRI') {
                            $this->primaryKey['name']           = $columns[$index]['Field'];
                            $this->primaryKey['auto_increment'] = ($columns[$index]['Extra'] == 'auto_increment');
                            return $this;
                        }
                        $index++;
                    }
                    throw new \PDOException('Primary key not found in this table.');
                } else {
                    throw new \PDOException('PDOStatement::fetchAll() function returned with false');
                }
            } else {
                throw new \PDOException('PDOStatement::execute() function returned with false');
            }

        } else {
            throw new \PDOException($this->name. 'is not found in this database');
        }

    }

    /**
     *
     * @return \Dominicus75\DataBase\DB a singleton instance of DB
     *
     */
    public function getDatabase(): DB { return $this->database; }

    /**
     *
     * @return string name of the current table (e. g. 'pages' or 'users')
     *
     */
    public function getName(): string { return $this->name; }

    /**
     *
     * @param string $column name of the requested column
     * @return bool Returns true if $column is found in $this->table, false otherwise
     *
     */
    public function hasColumn($column): bool { return array_key_exists($column, $this->columns); }

    /**
     *
     * @param string $column name of the requested column
     * @return array|null
     * array in (string)':column_name' => (int)column_type (\PDO::PARAM_STR or \PDO::PARAM_INT)
     * or null, if column does not exists
     *
     */
    public function getColumn($column): ?array {
        if($this->hasColumn($column)) {
            return $this->columns[$column];
        } else { return null; }
    }

    /**
     *
     * @return array
     * array in (string)'column_name' => [(string)':column_name' => (int)column_type]
     *
     */
    public function getColumns(): array { return $this->columns; }

    /**
     *
     * @return string name of the primary key column (e. g. 'id')
     *
     */
    public function getPrimaryKey(): string { return $this->primaryKey['name']; }

    /**
     * Check if a PRIMARY KEY is auto increment or not
     * @return bool
     *
     */
    public function isPrimaryAutoIncrement(): bool { return $this->primaryKey['auto_increment']; }

    /**
     * Run a select statement in this table
     * @param int|string|null $pk primary key
     * if this parameter is null, select all of this type from table
     */
    public function select(int|string|null $pk = null): array {

        switch($this->database->getDriver()) {
            case 'mysql':
                if(!is_null($pk)) {
                    $sql = "SELECT * FROM `".$this->name."` WHERE `".$this->getPrimaryKey()."` = '$pk'";
                } else {
                    $sql = "SELECT * FROM `".$this->name."`";
                }
                break;
        }

        $statement = $this->database->query($sql);

        if($statement->execute()) {
            $result = (is_null($pk)) ? $statement->fetchAll() : $statement->fetch(PDO::FETCH_ASSOC);
            return ($result) ? $result : [];
        } else {
            throw new \PDOException('PDOStatement::execute() function returned with false');
        }

    }

}
