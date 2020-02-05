<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * API charge status.
 *
 * @method static ChargeStatus ACTIVE()
 * @method static ChargeStatus ACCEPTED()
 * @method static ChargeStatus DECLINED()
 * @method static ChargeStatus CANCELLED()
 */
final class ChargeStatus implements ValueObject
{
    use EnumTrait;

    /**
     * Status: Active.
     *
     * @var int
     */
    public const ACTIVE = 0;

    /**
     * Status: Accepted.
     *
     * @var int
     */
    public const ACCEPTED = 1;

    /**
     * Status: Declines.
     *
     * @var int
     */
    public const DECLINED = 2;

    /**
     * Status: Cancelled.
     *
     * @var int
     */
    public const CANCELLED = 3;
}
