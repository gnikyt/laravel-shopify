<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeRole as ThemeRoleValue;

/**
 * Value object for theme's role (nullable).
 */
final class NullableThemeRole extends Nullable implements ThemeRoleValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return ThemeRole::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullThemeRole::class;
    }
}
