<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionToken as SessionTokenValue;

/**
 * Value object for session token (nullable).
 */
final class NullableSessionToken extends Nullable implements SessionTokenValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return SessionToken::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullSessionToken::class;
    }
}
