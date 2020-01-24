<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents a queries for charges.
 */
class Charge implements ChargeQuery
{
    /**
     * Get by shop ID and charge ID.
     *
     * @param int $shopId   The shop's ID for the charge.
     * @param int $chargeId The charge ID from Shopify.
     *
     * @return ChargeModel|null
     */
    public function getByShopIdAndChargeId(ShopId $shopId, ChargeId $chargeId): ?ChargeModel
    {
        return ChargeModel::where(
            [
                'shop_id'   => $shopId,
                'charge_id' => $chargeId,
            ]
        )->get();
    }
}
