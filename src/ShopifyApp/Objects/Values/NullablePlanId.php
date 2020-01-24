<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\NullPlanId;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;

/**
 * Value object for plan's ID (nullable).
 */
final class NullablePlanId extends Nullable implements PlanIdValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return PlanId::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullPlanId::class;
    }
}
