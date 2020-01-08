<?php

namespace OhMyBrew\ShopifyApp\DTO;

use Exception;

/**
 * Reprecents the base for DTO.
 */
abstract class AbstractDTO
{
    /**
     * Get a value from the object.
     *
     * @param string $key The key to get.
     *
     * @return mixed|Exception
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            // Get the value of the key
            return $this->{$key};
        }

        // Does not exist, throw exception
        $className = get_class($this);
        throw new Exception("Property {$key} does not exist on {$className}");
    }

    /**
     * Setting a value externally is disallowed.
     *
     * @param string $key   The key to use.
     * @param mixed  $value The value for the key.
     *
     * @return Exception
     */
    public function __set($key, $value)
    {
        throw new Exception('Setting a value on the DTO is disallowed');
    }
}
