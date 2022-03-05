<?php
/*
 * @file Config.php
 * @package Config
 * @copyright 2020 Domokos Endre János <domokos.endrejanos@gmail.com>
 * @license MIT License (https://opensource.org/licenses/MIT)
 */

namespace Dominicus75\Config;

class Config implements \ArrayAccess
{

    /**
     *
     * @var string fully qualified name of config directory
     *
     */
    private string $dir;

    /**
     *
     * @var string fully qualified filename of config file
     *
     */
    private string $file;

    /**
     *
     * @var array
     *
     */
    private array $container;

    /**
     *
     * @var bool if container differents from the config file it is true,
     * false if not
     *
     */
    private bool $changed = false;

    /**
     *
     * @param string $configFile name of config file
     * @param string $configDirectory fully qualified name of config directory
     * @param array $config config array with values
     * @return self
     * @throws NotFoundException, if $configFile is not exists
     * @throws NotReadableException, if $configFile is not readable
     * @throws NotWriteableException, if $configFile is not writeable
     *
     */
    public function __construct(string $configFile, string $configDirectory = '', array $config = []){

        if(empty($configDirectory)) {
            $dir = dirname(dirname(dirname(dirname(__DIR__)))).DIRECTORY_SEPARATOR."app".DIRECTORY_SEPARATOR."config";
        } else {
            $dir = $configDirectory;
        }

        if(is_readable($dir) && is_writeable($dir)) {
            $this->dir = $dir.DIRECTORY_SEPARATOR;
            if(is_file($this->dir.$configFile.".php")) {
            $this->file = $this->dir.$configFile.".php";
            if(!is_readable($this->file)) { throw new NotReadableException($this->file.' must be readable'); }
            if(!is_writeable($this->file)) { throw new NotWriteableException($this->file.' must be writeable'); }
            } else {
            $file = fopen($this->dir.$configFile.".php", 'x');
            fclose($file);
            $this->file = $this->dir.$configFile.".php";
            }
        } else {
            throw new NotFoundException($dir.' not exists or not readable/writeable');
        }

        if(empty($config)) {
            $this->container = require($this->file);
            $this->changed   = false;
        } else {
            $this->container = $config;
            $this->changed   = true;
        }

    }

    /**
     * Whether an offset exists
     * @param int|string $offset An offset to check for
     * @return bool Returns true, if offset exists, false, if not
     *
     */
    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->container);
    }


    /**
     * Returns the value at specified offset, if it exists, null otherwise
     * @param int|string $offset The offset to retrieve
     *
     */
    public function offsetGet(mixed $offset): mixed {
        return ($this->offsetExists($offset)) ? $this->container[$offset] : null;
    }


    /**
     * Assign a value to the specified offset
     * @param int|string $offset the offset to assign the value to
     * @param mixed $value the value to set
     * @return void
     *
     */
    public function offsetSet(mixed $offset, $value): void {
        if(is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
        $this->changed = true;
    }


    /**
     * Unset an offset
     * @param int|string $offset the offset to unset
     * @return void
     *
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->container[$offset]);
        $this->changed = true;
    }


    /**
     * Save the container to config file
     * 
     * @return bool
     * @throws NotWriteableException, if file not exists or is not writeable
     *
     */
    public function save(): bool {
        if($this->changed) {
            if(!is_writeable($this->dir)) { throw new NotWriteableException($this->dir.' is not writeable'); }
            if(is_writeable($this->file)) {
                if(false === file_put_contents($this->file, "<?php".PHP_EOL.PHP_EOL."return ".var_export($this->container, true) . ";".PHP_EOL, LOCK_EX)) {
                    return false;
                } else { return true; }
            } else {
                throw new NotWriteableException($this->file.' is not writeable');
            }
        } else { return true; }
    }


    public function getDirectory(): string { return $this->dir; }
    public function getFile(): string { return $this->file; }
    public function getConfig(): array { return $this->container; }

}
