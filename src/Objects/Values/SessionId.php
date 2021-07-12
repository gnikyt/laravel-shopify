<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\Scalars\StringTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\SessionId as SessionIdValue;

/**
 * Value object for session ID of a session token.
 */
final class SessionId implements SessionIdValue
{
    use StringTrait;
}
