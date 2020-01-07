<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Cancel's the shop's current plan (in the database).
 */
class CancelCurrentPlanAction
{
    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for shops.
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
     * @return bool
     */
    public function __invoke(string $shopDomain): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // Cancel the last charge
        $planCharge = $shop->planCharge();
        if ($planCharge && !$planCharge->isDeclined() && !$planCharge->isCancelled()) {
            $planCharge->cancel();
            return true;
        }

        return false;
    }
}
