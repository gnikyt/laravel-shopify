<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\Models\Charge;

/**
 * Reprecents a queries for charges.
 */
interface IChargeQuery
{
    /**
     * Get by shop ID and charge ID.
     *
     * @param int $shopId   The shop's ID for the charge.
     * @param int $chargeId The charge ID from Shopify.
     *
     * @return Charge|null
     */
    public function getByShopIdAndChargeId(int $shopId, int $chargeId): ?Charge;
}
