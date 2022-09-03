<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeId as ThemeIdValue;

/**
 * Value object for theme's ID (nullable).
 */
final class NullableThemeId extends Nullable implements ThemeIdValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return ThemeId::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullThemeId::class;
    }
}
