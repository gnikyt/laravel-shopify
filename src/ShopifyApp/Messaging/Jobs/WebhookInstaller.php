<?php

namespace OhMyBrew\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Webhook job responsible for handling installation of webhook listeners.
 */
class WebhookInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop's ID.
     *
     * @var int
     */
    protected $shopId;

    /**
     * Action for creating webhooks.
     *
     * @var callable
     */
    protected $createWebhooksAction;

    /**
     * Create a new job instance.
     *
     * @param int      $shopId               The shop's ID.
     * @param callable $createWebhooksAction Action for creating webhooks.
     *
     * @return self
     */
    public function __construct(int $shopId, callable $createWebhooksAction)
    {
        $this->shopId = $shopId;
        $this->createWebhooksAction = $createWebhooksAction;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        return call_user_func($this->createWebhooksAction, new ShopId($this->shopId));
    }
}
