<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Value object for access token (nullable).
 */
final class NullableAccessToken extends Nullable implements AccessTokenValue
{
    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return $this->value->isEmpty();
    }

    /**
     * @return string
     */
    protected static function nonNullImplementation(): string
    {
        return AccessToken::class;
    }

    /**
     * @return string
     */
    protected static function nullImplementation(): string
    {
        return NullAccessToken::class;
    }
}
