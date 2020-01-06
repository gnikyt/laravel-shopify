<?php

namespace OhMyBrew\ShopifyApp\DTO;

use OhMyBrew\ShopifyApp\DTO\AbstractDTO;

/**
 * Reprecents details for a plan.
 */
class PlanDetailsDTO extends AbstractDTO
{
    /**
     * Plan name.
     *
     * @var string
     */
    private $name;

    /**
     * Plan price.
     *
     * @var float
     */
    private $price;

    /**
     * Plan test or real?
     *
     * @var bool
     */
    private $test;

    /**
     * Plan trial days.
     *
     * @var int
     */
    private $trialDays;

    /**
     * Capped amount value.
     *
     * @var float|null
     */
    private $cappedAmount;

    /**
     * Terms for capped amount.
     *
     * @var string|null
     */
    private $cappedTerms;

    /**
     * Plan return URL.
     *
     * @var string|null
     */
    private $returnURL;

    /**
     * Constructor.
     *
     * @param string      $name         Plan name.
     * @param float       $price        Plan price.
     * @param boolean     $test         Plan test or real?
     * @param int         $trialDays    Plan trial days.
     * @param float|null  $cappedAmount Capped amount value.
     * @param string|null $cappedTerms  Terms for capped amount.
     * @param string|null $returnURL    Plan return URL.
     */
    public function __construct(
        string $name,
        float $price,
        bool $test,
        int $trialDays,
        ?float $cappedAmount,
        ?string $cappedTerms,
        ?string $returnURL
    ) {
        $this->name = $name;
        $this->price = $price;
        $this->test = $test;
        $this->trialDays = $trialDays;
        $this->cappedAmount = $cappedAmount;
        $this->cappedTerms = $cappedTerms;
        $this->returnURL = $returnURL;
    }
}
