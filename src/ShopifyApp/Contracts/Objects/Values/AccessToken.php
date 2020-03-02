<?php

namespace OhMyBrew\ShopifyApp\Contracts\Objects\Values;

use Funeralzone\ValueObjects\ValueObject;

/**
 * Access token value object.
 */
interface AccessToken extends ValueObject
{
    /**
     * Detects if the string is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool;
}
