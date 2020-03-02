<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\IntegerTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Value object for shop's ID.
 */
final class ShopId implements ValueObject
{
    use IntegerTrait;
}
