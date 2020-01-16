<?php

namespace OhMyBrew\ShopifyApp\DTO;

use ArrayIterator;
use Exception;
use IteratorAggregate;

/**
 * Reprecents the base for DTO.
 */
abstract class AbstractDTO implements IteratorAggregate
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
     * @return mixed|Exception
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            // Get the value of the key
            return $this->data[$key];
        }

        // Does not exist, throw exception
        $className = get_class($this);
        throw new Exception("Property {$key} does not exist on {$className}");
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
