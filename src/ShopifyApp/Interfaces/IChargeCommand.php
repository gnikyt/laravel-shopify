<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\DTO\CreateChargeDTO;
use OhMyBrew\ShopifyApp\DTO\DeleteChargeDTO;

/**
 * Reprecents commands for charges.
 */
interface IChargeCommand
{
    /**
     * Create a charge entry.
     *
     * @param CreateChargeDTO $charge The charge object.
     *
     * @return int
     */
    public function createCharge(CreateChargeDTO $chargeObj): int;

    /**
     * Deletes a charge for a shop.
     *
     * @param DeleteChargeDTO $deleteCharge The delete charge object.
     */
    public function deleteCharge(DeleteChargeDTO $deleteChargeObj): bool;
}
