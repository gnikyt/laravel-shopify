<?php

namespace Osiset\ShopifyApp\Exceptions;

use Exception;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Base exception for all exceptions of the package.
 * Mainly to handle render in production.
 */
abstract class BaseException extends Exception
{
    use ConfigAccessible;
}
