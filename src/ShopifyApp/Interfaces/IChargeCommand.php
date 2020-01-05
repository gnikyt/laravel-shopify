<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

/**
 * Reprecents commands for charges.
 */
interface IChargeCommand
{
    /**
     * Create a charge entry.
     *
     * @param int    $shopId   The shop for the charge.
     * @param int    $planId   The plan the charge is for.
     * @param int    $chargeId The charge ID from Shopify.
     * @param string $type     The type of charge (single or recurring).
     * @param string $status   The status of the charge.
     * @param array  $dates    Dates for the charge such as activation, trial ends, etc.
     * @param array  $planDetails The data about the plan such as name, length, etc.
     *
     * @return int
     */
    public function createCharge(
        int $shopId,
        int $planId,
        int $chargeId,
        string $type,
        string $status,
        array $dates,
        array $planDetails
    ): int;

    /**
     * Deletes a charge for a shop.
     *
     * @param int $shopId The shop to target.
     * @param int $chargeId The charge ID from Shopify to target.
     */
    public function deleteCharge(int $shopId, int $chargeId): bool;
}
