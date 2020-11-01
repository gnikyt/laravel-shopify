<?php

namespace Osiset\ShopifyApp\Services;

use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Basic helper class to set/get config values.
 *
 * This allows you to use the `ConfigAccessible` trait methods in situations where
 * you are unable to use the trait because you are not in a class scope
 * (e.g. when defining routes)
 */
class ConfigHelper
{
    use ConfigAccessible;

    /**
     * Get the config value for a key.
     *
     * @param string $key The key to lookup.
     *
     * @return mixed
     *
     * @see \Osiset\ShopifyApp\Traits\ConfigAccessible::getConfig()
     */
    public static function get(string $key)
    {
        return (new static)->getConfig($key);
    }

    /**
     * Sets a config value for a key.
     *
     * @param string $key   The key to use.
     * @param mixed  $value The value to set.
     *
     * @return void
     *
     * @see \Osiset\ShopifyApp\Traits\ConfigAccessible::setConfig()
     */
    public static function set(string $key, $value): void
    {
        (new static)->setConfig($key, $value);
    }

    /**
     * Set multiple config values.
     *
     * @param array|mixed[] $kvs
     *
     * @return void
     *
     * @see \Osiset\ShopifyApp\Traits\ConfigAccessible::setConfigArray()
     */
    public static function setArray(array $kvs): void
    {
        (new static)->setConfigArray($kvs);
    }
}
