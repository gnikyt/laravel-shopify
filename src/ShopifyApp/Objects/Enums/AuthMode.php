<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;

/**
 * API auth modes.
 *
 * @method static AuthMode OFFLINE()
 * @method static AuthMode PERUSER()
 */
final class AuthMode
{
    use EnumTrait;

    /**
     * Offline auth mode.
     *
     * @var string
     */
    public const OFFLINE = 'offline';

    /**
     * Per-user auth mode.
     *
     * @var string
     */
    public const PERUSER = 'per-user';
}
