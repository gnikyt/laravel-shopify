<?php

namespace OhMyBrew\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;

/**
 * API call method types.
 *
 * @method static ApiMethod GET()
 * @method static ApiMethod POST()
 * @method static ApiMethod PUT()
 * @method static ApiMethod DELEE()
 */
final class ApiMethod
{
    use EnumTrait;

    /**
     * HTTP method: GET
     *
     * @var string
     */
    public const GET = 'GET';

    /**
     * HTTP method: POST
     *
     * @var string
     */
    public const POST = 'POST';

    /**
     * HTTP method: PUT
     *
     * @var string
     */
    public const PUT = 'PUT';

    /**
     * HTTP method: DELETE
     *
     * @var string
     */
    public const DELETE = 'DELETE';
}
