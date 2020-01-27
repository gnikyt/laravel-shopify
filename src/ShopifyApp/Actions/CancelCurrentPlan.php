<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Cancel's the shop's current plan (in the database).
 */
class CancelCurrentPlan
{
    /**
     * Setup.
     *
     * @param ShopQuery $shopQuery The querier for shops.
     *
     * @return self
     */
    public function __construct(ShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param ShopId $shopId The shop ID.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Cancel the last charge
        $planCharge = $shop->planCharge();
        if ($planCharge && !$planCharge->isDeclined() && !$planCharge->isCancelled()) {
            $planCharge->cancel();

            return true;
        }

        return false;
    }
}
