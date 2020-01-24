<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\ValueObject;
use Funeralzone\ValueObjects\Scalars\IntegerTrait;

/**
 * Value object for charge's ID.
 */
final class ChargeId implements ValueObject
{
    use IntegerTrait;
}
