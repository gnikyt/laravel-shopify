<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
            ['user_model' => Config::get('auth.providers.users.model')]
        );

        if (Str::is('route_names.*', $key)) {
            // scope the Arr::get() call to the "route_names" array
            // to allow for dot-notation keys like "authenticate.oauth"
            // this is necessary because Arr::get() only finds dot-notation keys
            // if they are at the top level of the given array
            return Arr::get(
                $this->config['route_names'],
                Str::after($key, '.')
            );
        }

        return Arr::get($this->config, $key);
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

    /**
     * Set multiple config values.
     *
     * @param array|mixed[] $kvs
     *
     * @return void
     */
    public function setConfigArray(array $kvs): void
    {
        foreach ($kvs as $key => $value) {
            Config::set($key, $value);
        }
    }
}
