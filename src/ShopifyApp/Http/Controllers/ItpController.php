<?php

namespace Osiset\ShopifyApp\Http\Controllers;

use Illuminate\Routing\Controller;
use Osiset\ShopifyApp\Traits\ItpController as ItpControllerTrait;

/**
 * Responsible for handling ITP issues.
 */
class ItpController extends Controller
{
    use ItpControllerTrait;
}
