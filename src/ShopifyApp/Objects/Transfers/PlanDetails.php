<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

/**
 * Reprecents details for a plan.
 */
final class PlanDetails extends AbstractTransfer
{
    /**
     * Plan name.
     *
     * @var string
     */
    public $name;

    /**
     * Plan price.
     *
     * @var float
     */
    public $price;

    /**
     * Plan test or real?
     *
     * @var bool
     */
    public $test;
    /**
     * Plan trial days.
     *
     * @var int
     */
    public $trialDays;

    /**
     * Capped amount value (for usage charge).
     *
     * @var float|null
     */
    public $cappedAmount;

    /**
     * Capped terms (for usage charge).
     *
     * @var string|null
     */
    public $cappedTerms;

    /**
     * Plan return URL.
     *
     * @var string|null
     */
    public $returnUrl;
}
