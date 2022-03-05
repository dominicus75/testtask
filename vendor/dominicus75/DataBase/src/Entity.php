<?php

/*
 * @package DataBase
 * @copyright 2022 Domokos Endre János <domokos.endrejanos@gmail.com>
 * @license MIT License (https://opensource.org/licenses/MIT)
 */

namespace Dominicus75\DataBase;

use \Dominicus75\Config\Config;
use \ArrayAccess;
use \PDO;

class Entity extends Table {

    const EMPTY   = 0;
    const FILLED  = 1;
    const CREATED = 2;
    const UPDATED = 3;

    /**
     * @var array the properties of this Entity
     */
    protected array $properties = [];

    /**
     * @var array only updated properties
     */
    protected array $updated = [];

    /**
     * @var int State of this object
     */
    protected int $status = self::EMPTY;

    public function __construct(
        ArrayAccess|\Dominicus75\DataBase\DB $confOrInstance, 
        string $table, int|string|null $id = null
    ) {
        try {
            parent::__construct($confOrInstance, $table);
            $this->initProperties($id);
        } catch(\PDOException $pdoe) {
            throw $pdoe;
        }
    }
    
    /**
     * @return self
     */
    private function initProperties(int|string|null $id = null): self {

        foreach($this->columns as $name => $properties) {
            $this->properties[$name] = null;
        }

        if(!is_null($id)) {
            foreach($this->select($id) as $name => $value) {
                $this->setProperty($name, $value);
            }
            $this->setStatus(self::FILLED);
        }

        return $this;

    }

    /**
     * @return 
     */
    protected function setStatus(int $status): self { 
        $this->status = $status;
        return $this; 
    }

    /**
     *
     * @param string $name name of the requested property
     * @return bool Returns true if $name exists, false otherwise
     *
     */
    public function hasProperty(string $name): bool {
        return array_key_exists($name, $this->properties);
    }

    /**
     *
     * @param string $name name of the requested property
     * @return bool Returns true if $name property has value, false otherwise
     *
     */
    public function issetProperty(string $name): bool {
        return isset($this->properties[$name]);
    }

    /**
     *
     * @param string $name name of the property to set
     * @param mixed $value
     * @throws InvalidPropertyNameException if property is primary key or it already has value
     * @return self
     *
     */
    public function setProperty(string $name, $value): self {
        if(!$this->hasProperty($name)) {
            throw new InvalidPropertyNameException("The $name property is not exists");
        } elseif($name === $this->getPrimaryKey() && $this->isPrimaryAutoIncrement()) {
            throw new InvalidPropertyNameException("$name is a primary key and it has auto_increment attribute!");;
        } elseif(!$this->issetProperty($name)) {
            $this->properties[$name] = $value;
            $this->setStatus(self::CREATED);
            return $this;
        } else { throw new InvalidPropertyNameException("The $name property already has value, use updateProperty() method"); }
    }

    /**
     *
     * @param string $name name of the property to update
     * @param mixed $value
     * @return self
     * @throws InvalidPropertyNameException if property is a primary key, or it not exists
     *
     */
    public function updateProperty(string $name, $value): self {
        if(!$this->hasProperty($name)) {
            throw new InvalidPropertyNameException("The $name property is not exists");
        } elseif($name === $this->getPrimaryKey() && $this->isPrimaryAutoIncrement()) {
            throw new InvalidPropertyNameException("$name is a primary key and it is immutable!");
        } else {
            $this->updated[$name] = $value;
            $this->setStatus(self::UPDATED);
            return $this;
        }
    }

    /**
     *
     * @param string $name name of the requested property
     * @return mixed
     *
     */
    public function getProperty(string $name): mixed {
        return $this->hasProperty($name) ? $this->properties[$name] : false;
    }

    /**
     *
     * @param array $properties multi-properties in [$name => $value] form
     * @return self
     * @throws InvalidPropertyNameException if a property is primary key or it already has value
     *
     */
    public function setProperties(array $properties): self {
        try {
            foreach($properties as $name => $value) { $this->setProperty($name, $value); }
            return $this;
        } catch(InvalidPropertyNameException $e) { throw $e; }
    }

    /**
     *
     * @param array $properties multi-properties in [$name => $value] form
     * @return self
     * @throws InvalidPropertyNameException if a property is a primary key, or it not exists
     *
     */
    public function updateProperties(array $properties): self {
        foreach($properties as $name => $value) { $this->updateProperty($name, $value); }
        return $this;
    }

	/**
	 * 
	 * @return array
	 */
	function getProperties(): array { return $this->properties;	}
	
	/**
	 * 
	 * @return array
	 */
	function getUpdated(): array { return $this->updated; }

    /**
     * Insert this Entity into the table
     * @return bool true, if success, false otherwise 
     */
    public function insert(): bool {

        if($this->status == self::CREATED) {

            switch($this->database->getDriver()) {
                case 'mysql':

                    $fields = '';
                    $variables = '';
                
                    foreach($this->columns as $name => $properties) {
                        if($name == $this->primaryKey['name'] && $this->primaryKey['auto_increment']) {
                            continue;
                        } else {
                            $fields .= '`'.$name.'`, ';
                            $variables .= $properties['bind'].', ';
                        }
                    }

                    $fields = rtrim($fields, ', ');
                    $variables = rtrim($variables, ', ');
                
                    $sql= "INSERT INTO `".$this->name."` ($fields) VALUES ($variables)";

                    break;
            }

            $statement = $this->database->prepare($sql);

            foreach($this->columns as $name => $properties) {
                if($name == $this->getPrimaryKey() && $this->isPrimaryAutoIncrement()) {
                    continue;
                } else {
                    if(is_null($this->properties[$name]) && !$properties['nullable']) {
                        throw new InvalidPropertyValueException('This value can\'t be null');
                    } else {
                        $statement->bindParam($properties['bind'], $this->properties[$name], $properties['type']);
                    }
                }
            }
          
            return $statement->execute();

        } else {
            return false;
        }

    }

    /**
     * Update Entity in database
     * @return bool true, if success, false otherwise 
     */
    public function update(): bool {

        if($this->status == self::UPDATED) {

            switch($this->database->getDriver()) {
                case 'mysql':

                    $sql  = "UPDATE `".$this->name."` SET `";
                    foreach($this->getUpdated() as $column => $value) {
                      $sql .= $column."` = ".$this->columns[$column]['bind'].", ";
                    }
                    $sql = rtrim($sql, ', ');
                    $sql .= " WHERE `".$this->getPrimaryKey()."` = ".$this->columns[$this->getPrimaryKey()]['bind'];
                    
                    break;
            }

            $statement = $this->database->prepare($sql);
            $statement->bindParam($this->columns[$this->getPrimaryKey()]['bind'], $this->properties[$this->getPrimaryKey()], $this->columns[$this->getPrimaryKey()]['type']);
      
            foreach($this->getUpdated() as $column => $value) {
                if(is_null($value) && !$this->columns[$column]['nullable']) {
                    throw new InvalidPropertyValueException('This value can\'t be null');
                } else { 
                    $statement->bindParam($this->columns[$column]['bind'], $value, $this->columns[$column]['type']);
                }
            }

            return $statement->execute();
      
        } else {
            return false;
        }

    }

    /**
     * Delete a row from the datatable
     * @return bool true, if success, false otherwise 
     */
    public function delete(): bool {
  
        if($this->status == self::FILLED) {

            switch($this->database->getDriver()) {
                case 'mysql':

                    $pk   = $this->getPrimaryKey();
                    $sql  = "DELETE FROM `".$this->name;
                    $sql .= "` WHERE `".$pk."` = ".$this->columns[$pk]['bind'];
                    $statement = $this->database->prepare($sql);
                    $statement->bindParam(
                        $this->columns[$pk]['bind'],
                        $this->properties[$pk],
                        $this->columns[$pk]['type']
                    );
                         
                    break;
            }
      
            return $statement->execute();
      
        } else {
            return false;
        }

    }
	
}