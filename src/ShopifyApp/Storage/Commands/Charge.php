<?php

namespace OhMyBrew\ShopifyApp\Storage\Commands;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as ChargeCommand;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Models\Charge as ChargeModel;
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
        $charge->shop_id = $chargeObj->shopId->toNative();
        $charge->charge_id = $chargeObj->chargeId->toNative();
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function createUsageCharge(UsageChargeTransfer $chargeObj): int
    {
        // Create the charge
        $charge = new ChargeModel();
        $charge->shop_id = $chargeObj->shopId->toNative();
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
    public function cancelCharge(ChargeId $chargeId, ?string $expiresOn, ?string $trialEndsOn): bool
    {
        $charge = $this->query->getById($chargeId);
        $charge->status = ChargeStatus::CANCELLED()->toNative();
        $charge->cancelled_on = $expiresOn === null ? Carbon::today()->format('Y-m-d') : $expiresOn;
        $charge->expires_on = $trialEndsOn === null ? Carbon::today()->format('Y-m-d') : $trialEndsOn;

        return $charge->save();
    }
}
