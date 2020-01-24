<?php

namespace OhMyBrew\ShopifyApp\Commands;

use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as ChargeCommand;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;

/**
 * Reprecents the commands for charges.
 */
class Charge implements ChargeCommand
{
    /**
     * The querier.
     *
     * @var ChargeQuery
     */
    protected $query;

    /**
     * Init for charge command.
     */
    public function __construct(ChargeQuery $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createCharge(ChargeTransfer $chargeObj): int
    {
        $charge = new ChargeModel();
        $charge->shop_id = $chargeObj->shopId;
        $charge->charge_id = $chargeObj->chargeId;
        $charge->type = $chargeObj->chargeType;
        $charge->status = $chargeObj->chargeStatus;
        $charge->billing_on = $chargeObj->billingOn;
        $charge->activated_on = $chargeObj->activatedOn;
        $charge->trial_ends_on = $chargeObj->trialEndsOn;
        $charge->name = $chargeObj->planDetails->name;
        $charge->price = $chargeObj->planDetails->price;
        $charge->test = $chargeObj->planDetails->test;
        $charge->trial_days = $chargeObj->planDetails->trialDays;
        $charge->capped_amount = $chargeObj->planDetails->cappedAmount;
        $charge->terms = $chargeObj->planDetails->cappedTerms;

        // Save the charge
        $charge->save();
        
        return $charge->id;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteCharge(ShopId $shopId, ChargeId $chargeId): bool
    {
        $charge = $this->chargeQuery->getByShopIdAndChargeId($shopId, $chargeId);
        if (!$charge) {
            return false;
        }

        return $charge->delete();
    }


    /**
     * {@inheritDoc}
     */
    public function createUsageCharge(UsageChargeTransfer $chargeObj): int
    {
        // Create the charge
        $charge = new ChargeModel();
        $charge->shop_id = $chargeObj->shopId;
        $charge->charge_id = $chargeObj->chargeId;
        $charge->type = $chargeObj->chargeType;
        $charge->status = $chargeObj->chargeStatus;
        $charge->billing_on = $chargeObj->billingOn;
        $charge->price = $chargeObj->price;
        $charge->description = $chargeObj->description;
        $charge->reference_charge = $chargeObj->referenceCharge;

        // Save the charge
        $charge->save();
        
        return $charge->id;
    }
}
