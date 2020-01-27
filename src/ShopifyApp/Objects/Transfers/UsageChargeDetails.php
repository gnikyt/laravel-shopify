<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Transfers\AbstractTransfer;

/**
 * Reprecents details for a usage charge.
 */
class UsageChargeDetails extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param ChargeId $chargeId    The Shopify charge ID.
     * @param float    $price       Usage charge price.
     * @param string   $description Usage charge description.
     *
     * @return self
     */
    public function __construct(ChargeId $chargeId, float $price, string $description)
    {
        $this->data['chargeId'] = $chargeId;
        $this->data['price'] = $price;
        $this->data['description'] = $description;
    }
}
