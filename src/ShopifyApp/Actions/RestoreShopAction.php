<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Restore shop that was trashed.
 */
class RestoreShopAction
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param string $shopDomain The shop's domain.
     *
     * @return bool|null
     */
    public function __invoke(string $shopDomain)
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));
        if (!$shop->trashed()) {
            // Shop is not trashed, nothing to do
            return null;
        }

        // Restore the shop and charges
        $shop->restore();
        $shop->charges()->restore();

        return $shop->save();
    }
}
