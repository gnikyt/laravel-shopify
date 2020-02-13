<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\IntegerTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Value object for Shopify charge ID.
 */
final class ChargeReference implements ValueObject
{
    use IntegerTrait;
}
