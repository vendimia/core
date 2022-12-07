<?php

namespace Vendimia\Core;

use const Vendimia\{
    PROJECT_PATH,
    ENVIRONMENT
};

use InvalidArgumentException;
use ArrayAccess;

/**
 * Configuration manager
 */
class Config implements ArrayAccess
{
    /** Configuration parameters */
    private array $config = [];

    /** Alternate configuration files */
    private array $config_storage = [];

    /**
     * Creates a config object from an array
     */
    public function __construct(array $from_array = null)
    {
        if ($from_array) {
            $this->config = $from_array;
        }
    }

    /**
     * Adds a config file for default config or a named config
     */
    public function addFile(
        $source,
        $config_name = null
    )
    {
        if (!file_exists($source)) {
            throw new InvalidArgumentException("Configuration file '$source' inexistent");
        }

        if ($config_name) {
            $target_storage = &$this->config_storage[$config_name];
        } else {
            $target_storage = &$this->config;
        }

        $target_storage = require $source;

        // Si existe un fichero con el nombre de Vendimia\ENVIRONMENT, lo
        // cargamos también

        $alt_source = dirname($source) . '/' . basename($source, '.php') . '.' .
            ENVIRONMENT . '.php';

        if (file_exists($alt_source)) {
            $target_storage = array_merge(
                $target_storage,
                require $alt_source,
            );
        }
    }

    /**
     * Returns a configuration item
     */
    public function &get($index, $default = null)
    {
        if (key_exists($index, $this->config)) {
            return $this->config[$index];
        }

        return $default;
    }

    /**
     * Return a named config file
     */
    public function getNamedConfig($name) {
        if (key_exists($name, $this->config_storage)) {
            return $this->config_storage[$name];
        }
        throw new InvalidArgumentException("Named config '$name' inexistent");
    }

    /**
     * Magic method for accessing config item as $config->item
     */
    public function __get($index) {
        return $this->get($index);
    }

    /**
     * Implementation of ArrayAccess for accessing a named config
     */
    public function offsetExists(mixed $offset): bool
    {
        return key_exists($offset, $this->config_storage);
    }

    /**
     * Unused ArrayAccess method
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->config_storage[$offset];
    }

    /**
     * Unused ArrayAccess method
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {

    }

    /**
     * Unused ArrayAccess method
     */
    public function offsetUnset(mixed $offset): void
    {

    }
}