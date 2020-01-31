<?php

namespace OhMyBrew\ShopifyApp;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * The base "helper" class for this package.
 */
class ShopifyApp
{
    /**
     * Laravel application.
     *
     * @var Application
     */
    public $app;

    /**
     * The current shop.
     *
     * @var IShopModel
     */
    public $shop;

    /**
     * The querier for shops.
     *
     * @var IShopQuery
     */
    public $shopQuery;

    /**
     * Create a new confide instance.
     *
     * @param Application $app
     *
     * @return self
     */
    public function __construct(Application $app, IShopQuery $shopQuery)
    {
        $this->app = $app;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Gets/sets the current shop.
     *
     * @param ShopDomain|null $shopDomain The shop's domain.
     *
     * @return IShopModel
     */
    public function shop(ShopDomain $shopDomain = null): IShopModel
    {
        $shopifyDomain = $shopDomain ??
            ($this->app->make(ShopSession::class))->getDomain();

        if (!$this->shop && $shopifyDomain) {
            // Grab shop from database here
            $shop = $this->shopQuery->getByDomain($shopifyDomain, [], true);
            if (!$shop) {
                // Create the shop
                $model = Config::get('auth.providers.users.model');
                $shop = new $model();
                $shop->shopify_domain = $shopifyDomain;
                $shop->save();
            }

            // Update shop instance
            $this->shop = $shop;
        }

        return $this->shop;
    }

    /**
     * Gets an API instance.
     *
     * @return BasicShopifyAPI
     */
    public function api(): BasicShopifyAPI
    {
        $apiClass = Config::get('shopify-app.api_class');
        $api = new $apiClass();
        $api->setApiKey(Config::get('shopify-app.api_key'));
        $api->setApiSecret(Config::get('shopify-app.api_secret'));

        // Add versioning?
        $version = Config::get('shopify-app.api_version');
        if ($version !== null) {
            $api->setVersion($version);
        }

        // Enable basic rate limiting?
        if (Config::get('shopify-app.api_rate_limiting_enabled') === true) {
            $api->enableRateLimiting(
                Config::get('shopify-app.api_rate_limit_cycle'),
                Config::get('shopify-app.api_rate_limit_cycle_buffer')
            );
        }

        return $api;
    }

    /**
     * Ensures shop domain meets the specs.
     *
     * @param string $domain The shopify domain
     *
     * @return string|null
     */
    public function sanitizeShopDomain($domain): ?string
    {
        if (empty($domain)) {
            return null;
        }

        $configEndDomain = Config::get('shopify-app.myshopify_domain');
        $domain = strtolower(preg_replace('/https?:\/\//i', '', trim($domain)));

        if (strpos($domain, $configEndDomain) === false && strpos($domain, '.') === false) {
            // No myshopify.com ($configEndDomain) in shop's name
            $domain .= ".{$configEndDomain}";
        }

        // Return the host after cleaned up
        return parse_url("http://{$domain}", PHP_URL_HOST);
    }

    /**
     * HMAC creation helper.
     *
     * @param array $opts The options for building the HMAC
     *
     * @return string
     */
    public function createHmac(array $opts): string
    {
        // Setup defaults
        $data = $opts['data'];
        $raw = $opts['raw'] ?? false;
        $buildQuery = $opts['buildQuery'] ?? false;
        $buildQueryWithJoin = $opts['buildQueryWithJoin'] ?? false;
        $encode = $opts['encode'] ?? false;
        $secret = $opts['secret'] ?? Config::get('shopify-app.api_secret');

        if ($buildQuery) {
            //Query params must be sorted and compiled
            ksort($data);
            $queryCompiled = [];
            foreach ($data as $key => $value) {
                $queryCompiled[] = "{$key}=".(is_array($value) ? implode(',', $value) : $value);
            }
            $data = implode(
                ($buildQueryWithJoin ? '&' : ''),
                $queryCompiled
            );
        }

        // Create the hmac all based on the secret
        $hmac = hash_hmac('sha256', $data, $secret, $raw);

        // Return based on options
        return $encode ? base64_encode($hmac) : $hmac;
    }

    /**
     * Allows for sending a message to the logger for debugging.
     *
     * @param string $message The message to send.
     *
     * @return bool
     */
    public function debug(string $message): bool
    {
        if (!Config::get('shopify-app.debug')) {
            return false;
        }

        Log::debug($message);

        return true;
    }
}
