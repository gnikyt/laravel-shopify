<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;
use Funeralzone\ValueObjects\Scalars\IntegerTrait;

/**
 * Value object for plan's ID.
 */
final class PlanId implements PlanIdValue
{
    use IntegerTrait;
}
