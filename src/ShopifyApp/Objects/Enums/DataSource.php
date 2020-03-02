<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Source of data for Shopify requests.
 *
 * @method static DataSource INPUT()
 * @method static DataSource HEADER()
 * @method static DataSource REFERER()
 */
final class DataSource implements ValueObject
{
    use EnumTrait;

    /**
     * Input (GET/POST).
     *
     * @var int
     */
    public const INPUT = 0;

    /**
     * Header (X-Shopify).
     *
     * @var int
     */
    public const HEADER = 1;

    /**
     * Referer (Header).
     *
     * @var int
     */
    public const REFERER = 2;
}
