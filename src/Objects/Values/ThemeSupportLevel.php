<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\IntegerTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\ThemeSupportLevel as ThemeSupportLevelValue;

/**
 * Value object for shop's ID.
 */
final class ThemeSupportLevel implements ThemeSupportLevelValue
{
    use IntegerTrait;
}
