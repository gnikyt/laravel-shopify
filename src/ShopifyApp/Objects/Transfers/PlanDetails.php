<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use OhMyBrew\ShopifyApp\Objects\Transfers\AbstractTransfer;

/**
 * Reprecents details for a plan.
 */
class PlanDetails extends AbstractTransfer
{
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
        $this->data['name'] = $name;
        $this->data['price'] = $price;
        $this->data['test'] = $test;
        $this->data['trialDays'] = $trialDays;
        $this->data['cappedAmount'] = $cappedAmount;
        $this->data['cappedTerms'] = $cappedTerms;
        $this->data['returnURL'] = $returnURL;
    }
}
