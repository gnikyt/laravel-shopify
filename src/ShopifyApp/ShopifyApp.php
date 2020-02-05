<?php

namespace OhMyBrew\ShopifyApp;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Support\Facades\Log;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * The base "helper" class for this package.
 */
class ShopifyApp
{
    use ConfigAccessible;

    /**
     * The current shop.
     *
     * @var IShopModel
     */
    protected $shop;

    /**
     * The querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Create a new confide instance.
     *
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return self
     */
    public function __construct(IShopQuery $shopQuery, ShopSession $shopSession)
    {
        $this->shopQuery = $shopQuery;
        $this->shopSession = $shopSession;
    }

    /**
     * Gets/sets the current shop.
     *
     * @param ShopDomain|null $shopDomain The shop's domain.
     *
     * @return IShopModel|null
     */
    public function shop(ShopDomain $shopDomain = null): ?IShopModel
    {
        // Get the shop domain from params or from shop session
        $shopifyDomain = $shopDomain ?? $this->shopSession->getShop()->getDomain();

        if ($this->shop === null && !$shopifyDomain->isNull()) {
            // Grab shop from database here
            $shop = $this->shopQuery->getByDomain($shopifyDomain, [], true);

            if ($shop === null) {
                // Create the shop
                $model = $this->getConfig('user_model');
                $shop = new $model();
                $shop->name = $shopifyDomain->toNative();
                $shop->password = '';
                $shop->email = '';
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
        // Create the instance
        $apiClass = $this->getConfig('api_class');
        $api = new $apiClass();
        $api->setApiKey($this->getConfig('api_class'))
            ->setApiSecret($this->getConfig('api_secret'))
            ->setVersion($this->getConfig('api_version'));

        // Enable basic rate limiting?
        if ($this->getConfig('api_rate_limiting_enabled') === true) {
            $api->enableRateLimiting(
                $this->getConfig('api_rate_limit_cycle'),
                $this->getConfig('api_rate_limit_cycle_buffer')
            );
        }

        return $api;
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
        $secret = $opts['secret'] ?? $this->getConfig('api_secret');

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
        if (!$this->getConfig('debug')) {
            return false;
        }

        Log::debug($message);

        return true;
    }
}
