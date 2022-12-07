<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\NullTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeRole as ThemeRoleValue;

/**
 * Value object for theme's role (null).
 */
final class NullThemeRole implements ThemeRoleValue
{
    use NullTrait;
}
