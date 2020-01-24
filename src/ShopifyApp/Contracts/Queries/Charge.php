<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use OhMyBrew\ShopifyApp\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents a queries for charges.
 */
interface Charge
{
    /**
     * Get by shop ID and charge ID.
     *
     * @param ShopId   $shopId   The shop's ID for the charge.
     * @param ChargeId $chargeId The charge ID from Shopify.
     *
     * @return ChargeModel|null
     */
    public function getByShopIdAndChargeId(ShopId $shopId, ChargeId $chargeId): ?ChargeModel;
}
