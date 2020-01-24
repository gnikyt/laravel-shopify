<?php

namespace OhMyBrew\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\ValueObject;
use Funeralzone\ValueObjects\Scalars\StringTrait;

/**
 * Value object for shop's offline access token.
 */
final class AccessToken implements ValueObject
{
    use StringTrait;
}
