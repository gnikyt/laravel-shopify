<?php

namespace OhMyBrew\ShopifyApp\DTO;

/**
 * Reprecents create charge.
 */
class CreateChargeDTO
{
    /**
     * Shop ID.
     *
     * @var int
     */
    public $shopId;

    /**
     * Plan ID.
     *
     * @var int
     */
    public $planId;

    /**
     * Charge ID from Shopify.
     *
     * @var int
     */
    public $chargeId;

    /**
     * Charge type (recurring or single).
     *
     * @var string
     */
    public $chargeType;

    /**
     * Charge status.
     *
     * @var string
     */
    public $chargeStatus;

    /**
     * When the charge was activated.
     *
     * @var Carbon|null
     */
    public $activatedOn;

    /**
     * When the charge will be billed on.
     *
     * @var Carbon|null
     */
    public $billingOn;

    /**
     * When the trial ends on.
     *
     * @var Carbon|null
     */
    public $trialEndsOn;

    /**
     * Plan details.
     *
     * @var PlanDetailsDTO
     */
    public $planDetails;
}
