<?php

namespace OhMyBrew\ShopifyApp\Contracts\Commands;

use Illuminate\Support\Carbon as Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;

/**
 * Reprecents commands for charges.
 */
interface Charge
{
    /**
     * Create a charge entry.
     *
     * @param ChargeTransfer $chargeObj The charge object.
     *
     * @return int
     */
    public function createCharge(ChargeTransfer $chargeObj): int;

    /**
     * Deletes a charge for a shop.
     *
     * @param ChargeReference $chargeRef The charge ID from Shopify.
     * @param ShopId          $shopId   The shop's ID.
     */
    public function deleteCharge(ChargeReference $chargeRef, ShopId $shopId): bool;

    /**
     * Create a usage charge.
     *
     * @param UsageChargeTransfer $chargeObj The usage charge object.
     *
     * @return int
     */
    public function createUsageCharge(UsageChargeTransfer $chargeObj): int;

    /**
     * Cancels a charge for a shop.
     *
     * @param ChargeReference $chargeRef   The charge ID from Shopify.
     * @param Carbon          $expiresOn   Date of expiration.
     * @param Carbon          $trialEndsOn Date of when trial ends on based on remaining.
     *
     * @return bool
     */
    public function cancelCharge(
        ChargeReference $chargeRef,
        ?Carbon $expiresOn = null,
        ?Carbon $trialEndsOn = null
    ): bool;
}
