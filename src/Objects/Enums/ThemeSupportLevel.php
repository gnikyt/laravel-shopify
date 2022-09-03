<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Online Store 2.0 theme support
 */
class ThemeSupportLevel implements ValueObject
{
    use EnumTrait;

    /**
     * Support level: fully.
     *
     * @var int
     */
    public const FULL = 0;

    /**
     * Support level: partial.
     *
     * @var int
     */
    public const PARTIAL = 1;

    /**
     * Support level: unsupported.
     *
     * @var int
     */
    public const UNSUPPORTED = 2;
}
