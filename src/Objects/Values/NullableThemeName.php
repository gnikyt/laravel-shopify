<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeName as ThemeNameValue;

/**
 * Value object for theme's name (nullable).
 */
final class NullableThemeName extends Nullable implements ThemeNameValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return ThemeName::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullThemeName::class;
    }
}
