<?php

namespace OhMyBrew\ShopifyApp\Controllers;

use Illuminate\Routing\Controller;
use OhMyBrew\ShopifyApp\Traits\WebhookController as WebhookControllerTrait;

/**
 * Responsible for handling incoming webhook requests.
 */
class WebhookController extends Controller
{
    use WebhookControllerTrait;
}
