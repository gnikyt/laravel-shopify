<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\IntegerTrait;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;

/**
 * Value object for plan's ID.
 */
final class PlanId implements PlanIdValue
{
    use IntegerTrait;
}
