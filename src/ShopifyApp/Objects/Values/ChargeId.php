<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\IntegerTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Value object for charge ID.
 */
final class ChargeId implements ValueObject
{
    use IntegerTrait;
}
