<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;

/**
 * API types for charges.
 *
 * @method static ChargeType RECURRING()
 * @method static ChargeType ONETIME()
 * @method static ChargeType USAGE()
 * @method static ChargeType CREDIT()
 */
final class ChargeType
{
    use EnumTrait;

    /**
     * Charge: Recurring.
     *
     * @var int
     */
    public const RECURRING = 1;

    /**
     * Charge: One-time.
     *
     * @var int
     */
    public const ONETIME = 2;

    /**
     * Charge: Usage.
     *
     * @var int
     */
    public const USAGE = 3;

    /**
     * Charge: Credit.
     *
     * @var int
     */
    public const CREDIT = 4;
}
