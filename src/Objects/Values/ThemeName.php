<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\StringTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeName as ThemeNameValue;

/**
 * Value object for theme's name.
 */
final class ThemeName implements ThemeNameValue
{
    use StringTrait;
}
