<?php

namespace OhMyBrew\ShopifyApp;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Support\Facades\Log;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;
use OhMyBrew\ShopifyApp\Objects\Values\NullAccessToken;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;

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
     * The commands for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommnad;

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
     * @param IShopCommand $shopCommand The commands for shops.
     * @param IShopQuery   $shopQuery   The queier for shops.
     * @param ShopSession  $shopSession The shop session helper.
     *
     * @return self
     */
    public function __construct(
        IShopCommand $shopCommand,
        IShopQuery $shopQuery,
        ShopSession $shopSession
    ) {
        $this->shopCommand = $shopCommand;
        $this->shopQuery = $shopQuery;
        $this->shopSession = $shopSession;
    }

    /**
     * Gets the current shop based on the session
     *
     * @param ShopDomain $domain The shop domain to manually find.
     *
     * @return IShopModel|null
     */
    public function shop(ShopDomain $domain = null): ?IShopModel
    {
        // Get the shop domain from params or from shop session
        if ($this->shop === null) {
            // Update shop instance
            $shopDomain = $domain === null ? $this->shopSession->getShop()->getDomain() : $domain;
            $this->shop = $this->getOrCreateShop($domain);
        }

        return $this->shop;
    }

    /**
     * Gets the current API instance for the current shop.
     *
     * @return BasicShopifyAPI
     */
    public function api(): BasicShopifyAPI
    {
        return $this->shopSession->api();
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

    /**
     * Gets or creates the shop.
     * If a shop is trashed, it will still be found.
     * If a shop does not exist, it will be created.
     *
     * @param ShopDomainValue $domain The shop domain.
     *
     * @return IShopModel
     */
    protected function getOrCreateShop(ShopDomainValue $domain): IShopModel
    {
        // Grab shop from database here (domain, no withs, with trashed)
        $shop = $this->shopQuery->getByDomain($domain, [], true);

        if ($shop === null) {
            // Create the shop
            $id = $this->shopCommnad->create($domain, new NullAccessToken());
            $shop = $this->shopQuery->getById($id);
        }

        return $shop;
    }
}
