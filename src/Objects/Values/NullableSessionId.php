<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionId as SessionIdValue;

/**
 * Value object for session ID of a session token (nullable).
 */
final class NullableSessionId extends Nullable implements SessionIdValue
{
    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return SessionId::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullSessionId::class;
    }
}
