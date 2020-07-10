<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Charge interval with annual support.
 *
 * @method static ChargeInterval EVERY_30_DAYS()
 * @method static ChargeInterval ANNUAL()
 */
class ChargeInterval implements ValueObject
{
    use EnumTrait;

    /**
     * Interval: Monthly.
     *
     * @var int
     */
    public const EVERY_30_DAYS = 1;

    /**
     * Interval: Annual.
     *
     * @var int
     */
    public const ANNUAL = 2;
}
