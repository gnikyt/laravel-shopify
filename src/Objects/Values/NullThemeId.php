<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\NullTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeId as ThemeIdValue;

/**
 * Value object for theme's ID (null).
 */
final class NullThemeId implements ThemeIdValue
{
    use NullTrait;
}
