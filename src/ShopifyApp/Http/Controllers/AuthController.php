<?php

namespace Osiset\ShopifyApp\Controllers;

use Illuminate\Routing\Controller;
use Osiset\ShopifyApp\Traits\AuthController as AuthControllerTrait;

/**
 * Responsible for authenticating the shop.
 */
class AuthController extends Controller
{
    use AuthControllerTrait;
}
