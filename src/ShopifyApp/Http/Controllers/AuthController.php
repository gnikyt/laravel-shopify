<?php

namespace OhMyBrew\ShopifyApp\Controllers;

use Illuminate\Routing\Controller;
use OhMyBrew\ShopifyApp\Traits\AuthControllerTrait;

/**
 * Responsible for authenticating the shop.
 */
class AuthController extends Controller
{
    use AuthControllerTrait;
}
