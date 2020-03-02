<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Support\Facades\Config;

/**
 * Allows for getting of config data easily for the package.
 */
trait ConfigAccessible
{
    /**
     * Get the config value for a key.
     *
     * @param string $key The key to lookup.
     *
     * @return mixed
     */
    public function getConfig(string $key)
    {
        $this->config = array_merge(
            Config::get('shopify-app'),
            [
                'user_model' => Config::get('auth.providers.users.model'),
            ]
        );

        return $this->config[$key];
    }

    /**
     * Sets a config value for a key.
     *
     * @param string $key   The key to use.
     * @param mixed  $value The value to set.
     *
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        Config::set($key, $value);
    }

    public function setConfigArray(array $kvs): void
    {
        foreach ($kvs as $key => $value) {
            Config::set($key, $value);
        }
    }
}
