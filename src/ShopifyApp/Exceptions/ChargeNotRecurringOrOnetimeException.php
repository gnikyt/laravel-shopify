<?php

namespace Osiset\ShopifyApp\Exceptions;

/**
 * Exception for when a charge is not recurring or one-time but is attempting to be cancelled.
 */
class ChargeNotRecurringOrOnetimeException extends BaseException
{
}
