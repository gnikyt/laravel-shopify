<?php

namespace OhMyBrew\ShopifyApp\Controllers;

use Illuminate\Routing\Controller;
use OhMyBrew\ShopifyApp\Traits\BillingController as BillingControllerTrait;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
class BillingController extends Controller
{
    use BillingControllerTrait;
}
