<?php

namespace OhMyBrew\ShopifyApp\Commands;

use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;
use OhMyBrew\ShopifyApp\Models\Charge;

/**
 * Reprecents the commands for charges.
 */
class ChargeCommand implements IChargeCommand
{
    /**
     * The querier.
     *
     * @var IChargeQuery
     */
    protected $query;

    /**
     * Init for charge command.
     */
    public function __construct(IChargeQuery $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createCharge(
        int $shopId,
        int $planId,
        int $chargeId,
        string $type,
        string $status,
        array $dates,
        array $planDetails
    ): int {
        $charge = new Charge();
        $charge->shop_id = $shopId;
        $charge->charge_id = $chargeId;
        $charge->type = $type;
        $charge->status = $status;

        // Set the dates
        foreach ($dates as $key => $value) {
            $charge->{$key} = $value; 
        }

        // Set the plan's details as reference
        foreach ($planDetails as $key => $value) {
            $charge->{$key} = $value;
        }

        // Save the charge
        $charge->save();
        
        return $charge->id;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteCharge(int $shopId, int $chargeId): bool
    {
        $charge = $this->chargeQuery->getByShopIdAndChargeId($shopId, $chargeId);
        if (!$charge) {
            return false;
        }

        return $charge->delete();
    }
}