<?php

namespace OhMyBrew\ShopifyApp\DTO;

/**
 * Reprecents details for a plan.
 */
class PlanDetailsDTO
{
    /**
     * Plane name.
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
     * Capped amount value.
     *
     * @var float
     */
    public $cappedAmount;

    /**
     * Terms for capped amount.
     *
     * @var string
     */
    public $cappedTerms;

    /**
     * Plan return URL.
     *
     * @var string
     */
    public $returnURL;
}
