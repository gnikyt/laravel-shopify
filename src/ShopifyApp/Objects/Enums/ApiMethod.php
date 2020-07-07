<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * API call method types.
 *
 * @method static ApiMethod GET()
 * @method static ApiMethod POST()
 * @method static ApiMethod PUT()
 * @method static ApiMethod DELETE()
 */
final class ApiMethod implements ValueObject
{
    use EnumTrait;

    /**
     * HTTP method: GET.
     *
     * @var int
     */
    public const GET = 0;

    /**
     * HTTP method: POST.
     *
     * @var int
     */
    public const POST = 1;

    /**
     * HTTP method: PUT.
     *
     * @var int
     */
    public const PUT = 2;

    /**
     * HTTP method: DELETE.
     *
     * @var int
     */
    public const DELETE = 3;
}
