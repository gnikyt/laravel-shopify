<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use ArrayIterator;
use Exception;
use IteratorAggregate;

/**
 * Reprecents the base for DTO.
 */
abstract class AbstractTransfer implements IteratorAggregate
{
    /**
     * The data container.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Get a value from the object.
     *
     * @param string $key The key to get.
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            // Get the value of the key
            return $this->data[$key];
        }

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
     * @return void
     */
    public function __set(string $key, $value): void
    {
        // Not allowed, throw exception
        $className = get_class($this);
        throw new Exception("Setting properties for transfer class {$className}");
    }

    /**
     * Iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }
}
