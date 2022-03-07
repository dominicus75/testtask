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
     * $primaryKey[] = ['name' => string, 'auto_increment' => bool]
     *
     */
    protected array $primaryKeys;

    /**
      * @var array Relations of this table
     */
    protected array $relations;

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
            $this->setPrimaryKeys();
            $this->setRelations();

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
                    foreach($columns as $column) {
                        $type = (preg_match("/(char|text|date|enum|set)/is", $column['Type'])) ? PDO::PARAM_STR : PDO::PARAM_INT;
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
    private function setPrimaryKeys(): self {

        if($this->database->hasTable($this->name)) {

            switch($this->database->getDriver()) {
                case 'mysql':
                    $sql = "SHOW COLUMNS FROM `".$this->name."`";
                    break;
            }

            $statement = $this->database->query($sql);

            if($statement->execute()) {
                if($columns = $statement->fetchAll()) { 
                    foreach($columns as $index => $column) { 
                        if($column['Key'] == 'PRI') {
                            $this->primaryKeys[$index]['name']           = $column['Field'];
                            $this->primaryKeys[$index]['auto_increment'] = ($column['Extra'] == 'auto_increment');
                        }
                    }
                    return $this;
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
     * Set relations of this table
     * @return self
     */
    private function setRelations(): self {

        if($this->database->hasTable($this->name)) {

            switch($this->database->getDriver()) {
                case 'mysql':
                    $sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME ";
                    $sql .= "FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE ";
                    $sql .= "WHERE REFERENCED_TABLE_NAME = '".$this->name."'";
                    break;
            }

            $statement = $this->database->query($sql);

            if($statement->execute()) {
                if($columns = $statement->fetchAll()) { 
                    foreach($columns as $index => $column) { 
                        $this->relations[$index]['referenced_column'] = $column['REFERENCED_COLUMN_NAME'];
                        $this->relations[$index]['referer_column']    = $column['COLUMN_NAME'];
                        $this->relations[$index]['referer_table']     = $column['TABLE_NAME'];
                    }
                    return $this;
                } else {
                    $this->relations = [];
                    return $this;
                    }
            } else {
                $this->relations = [];
                return $this;
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
     * @return array names of the primary keys columns (e. g. 'id')
     *
     */
    public function getPrimaryKeys(): array { 
        $result = [];
        foreach($this->primaryKeys as $pk) { $result[] = $pk['name']; }
        return $result; 
    }

    /**
     * 
     * Check if the given key is PRIMARY KEY and it has auto increment or not
     * @return bool
     *
     */
    public function isPrimaryAndAutoIncrement(int|string $key): bool {
        foreach($this->primaryKeys as $pk) { 
            if(in_array($key, $pk) && $pk['auto_increment']) { return true; }
        }
        return false;
    }

    /**
     * 
     * @return array set of relations
     * 
     */
    public function getRelations(): array { return $this->relations; }

    /**
     * 
     * Checks if the given key exists in the given table
     * @return bool 
     * 
     */
    public function foreignKeyExists(string $table, string $key): bool {

        if(empty($this->relations)) { return false; }

        foreach($this->relations as $relation) { 
            if(in_array($key, $relation) && in_array($table, $relation)) { return true; }
        }

        return false;
    }

    /**
     * 
     * @param int|string $key - key what looking for
     * @param mixed $value - value what looking for
     * @param string $table - table where looking for, optional
     * Checks if the given value exists in this table
     * @return bool
     * 
     */
    public function valueExists(mixed $key, mixed $value, string $table = ''): bool {

        if(empty($table)) {
            $tbl = $this->getName();
            if(!$this->hasColumn($key)) { return false; }
        } elseif($this->database->hasTable($table)) {
            $tbl = $table;
        } else { return false; }

        $sql = "SELECT EXISTS(SELECT `$key` FROM `$tbl` WHERE `$key` = :value)";
        $statement = $this->database->prepare($sql);
        $statement->bindParam(':value', $value);

        if($statement->execute()) { return (bool) $statement->fetchColumn(); } 

        return false;   

    }

    /**
     * 
     * @param array $keys - primary key(s) and its vale what looking for
     * @param string $table - table where looking for, optional
     * Checks if the given value exists in this table
     * @return bool
     * 
     */
    public function pkValueExists(array $keys, string $table = ''): bool {

        $fields = '';
        $binds  = '';
        $where  = '';

        foreach($keys as $key => $value) {
            $fields .= "`$key`, ";
            $binds  .= ":$key, ";
            $where  .= "`$key` = :$key AND ";
        }
        
        $fields = rtrim($fields, ', ');
        $binds  = rtrim($binds, ', ');
        $where  = rtrim($where, ', AND ');

        if(empty($table)) {
            $tbl = $this->getName();
        } elseif($this->database->hasTable($table)) {
            $tbl = $table;
        } else { return false; }

        $sql = "SELECT EXISTS(SELECT $fields FROM `$tbl` WHERE $where)";
        $statement = $this->database->prepare($sql); 

        foreach($keys as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue(":$key", $value, $type); 
        }

        if($statement->execute()) { 
            return (bool) $statement->fetchColumn(); 
        } 

        return false;   
             
    }

}
