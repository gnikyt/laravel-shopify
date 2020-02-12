<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;

/**
 * Reprecents a queries for charges.
 */
interface Charge
{
    /**
     * Get by ID.
     *
     * @param ChargeId $chargeId The charge ID.
     * @param array    $with     The relations to eager load.
     *
     * @return ChargeModel|null
     */
    public function getById(ChargeId $planId, array $with = []): ?ChargeModel;

    /**
     * Get by shop ID and charge ID.
     *
     * @param ChargeId $chargeId The charge ID from Shopify.
     * @param ShopId   $shopId   The shop's ID for the charge.
     *
     * @return ChargeModel|null
     */
    public function getByChargeIdAndShopId(ChargeId $chargeId, ShopId $shopId): ?ChargeModel;
}
