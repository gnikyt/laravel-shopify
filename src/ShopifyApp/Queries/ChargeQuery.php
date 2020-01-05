<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;

/**
 * Reprecents a queries for charges.
 */
class ChargeQuery implements IChargeQuery
{
    /**
     * Get by shop ID and charge ID.
     *
     * @param int $shopId   The shop's ID for the charge.
     * @param int $chargeId The charge ID from Shopify.
     *
     * @return Charge|null
     */
    public function getByShopIdAndChargeId(int $shopId, int $chargeId): ?Charge
    {
        return Charge::where(
            [
                'shop_id'   => $shopId,
                'charge_id' => $chargeId,
            ]
        )->get();
    }
}
