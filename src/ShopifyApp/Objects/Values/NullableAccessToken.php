<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Nullable;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\NullAccessToken;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Value object for access token (nullable).
 */
final class NullableAccessToken extends Nullable implements AccessTokenValue
{
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
