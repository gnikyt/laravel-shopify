<?php

namespace Osiset\ShopifyApp\Contracts;

use Illuminate\Contracts\Queue\ShouldQueue;
use Osiset\ShopifyApp\Contracts\Objects\Values\WebhookId;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

interface WebhookJob extends ShouldQueue
{
    /**
     * Create a new job instance.
     *
     * @param ShopDomain|string $shopId    The shop Domain.
     * @param WebhookId|string  $webhookId The webhooks ID.
     * @param stdClass   $data      The webhook payload.
     *
     * @return void
     */
    public function __construct(string $shopId, string $webhookId, stdClass $data);
}
