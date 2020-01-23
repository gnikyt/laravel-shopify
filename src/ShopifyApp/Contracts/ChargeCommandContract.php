<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\DTO\ChargeDTO;
use OhMyBrew\ShopifyApp\DTO\UsageChargeDTO;

/**
 * Reprecents commands for charges.
 */
interface IChargeCommand
{
    /**
     * Create a charge entry.
     *
     * @param ChargeDTO $chargeObj The charge object.
     *
     * @return int
     */
    public function createCharge(ChargeDTO $chargeObj): int;

    /**
     * Deletes a charge for a shop.
     *
     * @param int $shopId   The shop's ID.
     * @param int $chargeId The charge ID from Shopify.
     */
    public function deleteCharge(int $shopId, int $chargeId): bool;

    /**
     * Create a usage charge.
     *
     * @param UsageChargeDTO $chargeObj The usage charge object.
     *
     * @return integer
     */
    public function createUsageCharge(UsageChargeDTO $chargeObj): int;
}
