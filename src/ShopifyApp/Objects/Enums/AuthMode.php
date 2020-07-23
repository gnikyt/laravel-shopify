<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * API auth modes.
 *
 * @method static AuthMode OFFLINE()
 * @method static AuthMode PERUSER()
 */
final class AuthMode implements ValueObject
{
    use EnumTrait;

    /**
     * Offline auth mode.
     *
     * @var int
     */
    public const OFFLINE = 0;

    /**
     * Per-user auth mode.
     *
     * @var int
     */
    public const PERUSER = 1;
}
