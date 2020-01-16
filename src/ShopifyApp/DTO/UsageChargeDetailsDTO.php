<?php

namespace OhMyBrew\ShopifyApp\DTO;

use OhMyBrew\ShopifyApp\DTO\AbstractDTO;

/**
 * Reprecents details for a usage charge.
 */
class UsageChargeDetailsDTO extends AbstractDTO
{
    /**
     * Constructor.
     *
     * @param int    $chargeId    The Shopify charge ID.
     * @param float  $price       Usage charge price.
     * @param string $description Usage charge description.
     *
     * @return self
     */
    public function __construct(int $chargeId, float $price, string $description)
    {
        $this->data['chargeId'] = $chargeId;
        $this->data['price'] = $price;
        $this->data['description'] = $description;
    }
}
