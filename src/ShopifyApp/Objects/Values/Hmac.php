<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\StringTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Value object for HMAC.
 */
final class Hmac implements ValueObject
{
    use StringTrait;

    /**
     * {@inheritDoc}
     */
    public function isSame(ValueObject $object): bool
    {
        return hash_equals($this->toNative(), $object->toNative());
    }
}
