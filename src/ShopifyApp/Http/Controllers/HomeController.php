<?php

namespace Osiset\ShopifyApp\Http\Controllers;

use Illuminate\Routing\Controller;
use Osiset\ShopifyApp\Traits\HomeController as HomeControllerTrait;

/**
 * Responsible for showing the main homescreen for the app.
 */
class HomeController extends Controller
{
    use HomeControllerTrait;
}
