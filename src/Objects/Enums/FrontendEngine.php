<?php

namespace Osiset\ShopifyApp\Objects\Enums;

use Funeralzone\ValueObjects\Enums\EnumTrait;
use Funeralzone\ValueObjects\ValueObject;

/**
 * Online Store 2.0 theme support
 */
class FrontendEngine implements ValueObject
{
    use EnumTrait;

    /**
     * Laravel Blade
     *
     * @var int
     */
    public const BLADE = 0;

    /**
     * Vue.js
     *
     * @var int
     */
    public const VUE = 1;

    /**
     * React
     *
     * @var int
     */
    public const REACT = 2;
}
