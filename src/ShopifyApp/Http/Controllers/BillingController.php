<?php

namespace Osiset\ShopifyApp\Controllers;

use Illuminate\Routing\Controller;
use Osiset\ShopifyApp\Traits\BillingController as BillingControllerTrait;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
class BillingController extends Controller
{
    use BillingControllerTrait;
}
