<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * API types for plans.
 *
 * @method static PlanType RECURRING()
 * @method static PlanType ONETIME()
 */
final class PlanType implements ValueObject
{
    use EnumTrait;

    /**
     * Plan: Recurring.
     *
     * @var int
     */
    public const RECURRING = 1;

    /**
     * Plan: One-time.
     *
     * @var int
     */
    public const ONETIME = 2;
}
