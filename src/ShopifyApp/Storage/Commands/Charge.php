<?php

namespace Osiset\ShopifyApp\Storage\Commands;

use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Contracts\Commands\Charge as ChargeCommand;
use Osiset\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use Osiset\ShopifyApp\Storage\Models\Charge as ChargeModel;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\ShopId;

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
    public function make(ChargeTransfer $chargeObj): ChargeId
    {
        /**
         * Is an instance of Carbon?
         *
         * @param mixed $obj The object to check.
         *
         * @return bool
         */
        $isCarbon = function ($obj): bool {
            return $obj instanceof Carbon;
        };

        $charge = new ChargeModel();
        $charge->plan_id = $chargeObj->planId->toNative();
        $charge->user_id = $chargeObj->shopId->toNative();
        $charge->charge_id = $chargeObj->chargeReference->toNative();
        $charge->type = $chargeObj->chargeType->toNative();
        $charge->status = $chargeObj->chargeStatus->toNative();
        $charge->name = $chargeObj->planDetails->name;
        $charge->price = $chargeObj->planDetails->price;
        $charge->interval = $chargeObj->planDetails->interval;
        $charge->test = $chargeObj->planDetails->test;
        $charge->trial_days = $chargeObj->planDetails->trialDays;
        $charge->capped_amount = $chargeObj->planDetails->cappedAmount;
        $charge->terms = $chargeObj->planDetails->cappedTerms;
        $charge->activated_on = $isCarbon($chargeObj->activatedOn) ? $chargeObj->activatedOn->format('Y-m-d') : null;
        $charge->billing_on = $isCarbon($chargeObj->billingOn) ? $chargeObj->billingOn->format('Y-m-d') : null;
        $charge->trial_ends_on = $isCarbon($chargeObj->trialEndsOn) ? $chargeObj->trialEndsOn->format('Y-m-d') : null;

        // Save the charge
        $charge->save();

        return $charge->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ChargeReference $chargeRef, ShopId $shopId): bool
    {
        $charge = $this->query->getByReferenceAndShopId($chargeRef, $shopId);

        return $charge === null ? false : $charge->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function makeUsage(UsageChargeTransfer $chargeObj): ChargeId
    {
        // Create the charge
        $charge = new ChargeModel();
        $charge->user_id = $chargeObj->shopId->toNative();
        $charge->charge_id = $chargeObj->chargeReference->toNative();
        $charge->type = $chargeObj->chargeType->toNative();
        $charge->status = $chargeObj->chargeStatus->toNative();
        $charge->billing_on = $chargeObj->billingOn->format('Y-m-d');
        $charge->price = $chargeObj->details->price;
        $charge->description = $chargeObj->details->description;
        $charge->reference_charge = $chargeObj->details->chargeReference->toNative();

        // Save the charge
        $charge->save();

        return $charge->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(
        ChargeReference $chargeRef,
        ?Carbon $expiresOn = null,
        ?Carbon $trialEndsOn = null
    ): bool {
        $charge = $this->query->getByReference($chargeRef);
        $charge->status = ChargeStatus::CANCELLED()->toNative();
        $charge->cancelled_on = $expiresOn === null ? Carbon::today()->format('Y-m-d') : $expiresOn->format('Y-m-d');
        $charge->expires_on = $trialEndsOn === null ? Carbon::today()->format('Y-m-d') : $trialEndsOn->format('Y-m-d');

        return $charge->save();
    }
}
