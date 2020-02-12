<?php

namespace OhMyBrew\ShopifyApp\Storage\Commands;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as ChargeCommand;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

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
     * {@inheritdoc}
     */
    public function createCharge(ChargeTransfer $chargeObj): int
    {
        $charge = new ChargeModel();
        $charge->user_id = $chargeObj->shopId->toNative();
        $charge->charge_id = $chargeObj->chargeId->toNative();
        $charge->type = $chargeObj->chargeType->toNative();
        $charge->status = $chargeObj->chargeStatus->toNative();
        $charge->name = $chargeObj->planDetails->name;
        $charge->price = $chargeObj->planDetails->price;
        $charge->test = $chargeObj->planDetails->test;
        $charge->trial_days = $chargeObj->planDetails->trialDays;
        $charge->capped_amount = $chargeObj->planDetails->cappedAmount;
        $charge->terms = $chargeObj->planDetails->cappedTerms;
        $charge->activated_on = $chargeObj->activatedOn instanceof Carbon ?
            $chargeObj->activatedOn->format('Y-m-d') :
            null;
        $charge->billing_on = $chargeObj->billingOn instanceof Carbon ?
            $chargeObj->billingOn->format('Y-m-d') :
            null;
        $charge->trial_ends_on = $chargeObj->trialEndsOn instanceof Carbon ?
            $chargeObj->trialEndsOn->format('Y-m-d') :
            null;
        
        // Save the charge
        $charge->save();

        return $charge->id;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCharge(ShopId $shopId, ChargeId $chargeId): bool
    {
        $charge = $this->query->getByChargeIdAndShopId($chargeId, $shopId);
        if (!$charge) {
            return false;
        }

        return $charge->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function createUsageCharge(UsageChargeTransfer $chargeObj): int
    {
        // Create the charge
        $charge = new ChargeModel();
        $charge->user_id = $chargeObj->shopId->toNative();
        $charge->charge_id = $chargeObj->chargeId->toNative();
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

    /**
     * {@inheritdoc}
     */
    public function cancelCharge(ChargeId $chargeId, ?Carbon $expiresOn = null, ?Carbon $trialEndsOn = null): bool
    {
        $charge = $this->query->getById($chargeId);
        $charge->status = ChargeStatus::CANCELLED()->toNative();
        $charge->cancelled_on = $expiresOn === null ? Carbon::today()->format('Y-m-d') : $expiresOn->format('Y-m-d');
        $charge->expires_on = $trialEndsOn === null ? Carbon::today()->format('Y-m-d') : $trialEndsOn->format('Y-m-d');

        return $charge->save();
    }
}
