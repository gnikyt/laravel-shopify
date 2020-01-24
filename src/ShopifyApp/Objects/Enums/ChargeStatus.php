<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;

/**
 * API charge status.
 *
 * @method static ChargeStatus ACTIVE()
 * @method static ChargeStatus ACCEPTED()
 * @method static ChargeStatus DECLINED()
 * @method static ChargeStatus CANCELLED()
 */
final class ChargeStatus
{
    use EnumTrait;

    /**
     * Status: Active
     *
     * @var string
     */
    public const ACTIVE = 'active';

    /**
     * Status: Accepted
     *
     * @var string
     */
    public const ACCEPTED = 'accepted';

    /**
     * Status: Declines
     *
     * @var string
     */
    public const DECLINED = 'declined';

    /**
     * Status: Cancelled
     *
     * @var string
     */
    public const CANCELLED = 'cancelled';
}
