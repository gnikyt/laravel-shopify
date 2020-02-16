<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use ArrayIterator;
use Exception;
use IteratorAggregate;
use JsonSerializable;

/**
 * Reprecents the base for DTO.
 */
abstract class AbstractTransfer implements IteratorAggregate, JsonSerializable
{
    /**
     * Get a value from the object.
     *
     * @param string $key The key to get.
     *
     * @throws Exception
     *
     * @return void
     */
    public function __get(string $key): void
    {
        // Does not exist, throw exception
        $className = get_class($this);
        throw new Exception("Property {$key} does not exist on transfer class {$className}");
    }

    /**
     * Disable setting abilities to make object immutable.
     *
     * @param string $key   The key attempting to set.
     * @param mixed  $value The value attempting to set.
     *
     * @throws Exception
     *
     * @return void
     */
    public function __set(string $key, $value): void
    {
        // Not allowed, throw exception
        $className = get_class($this);
        throw new Exception("Setting property {$key} for transfer class {$className} is not allowed");
    }

    /**
     * Iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(get_object_vars($this));
    }

    /**
     * Serialize the class to JSON.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode(get_object_vars($this));
    }
}
