<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\ValueObject;
use Funeralzone\ValueObjects\Scalars\IntegerTrait;

/**
 * Value object for plan's ID.
 */
final class PlanId implements ValueObject
{
    use IntegerTrait;
}
