<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Osiset\ShopifyApp\Objects\Values\ShopId;

/**
 * Webhook job responsible for handling installation of webhook listeners.
 */
class WebhookInstaller implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
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
     * The webhooks to add.
     *
     * @var array
     */
    protected $configWebhooks;

    /**
     * Create a new job instance.
     *
     * @param ShopId $shopId               The shop ID.
     * @param string $createWebhooksAction Action for creating webhooks.
     * @param array  $configWebhooks       The webhooks to add.
     *
     * @return void
     */
    public function __construct(ShopId $shopId, callable $createWebhooksAction, array $configWebhooks)
    {
        $this->shopId = $shopId;
        $this->createWebhooksAction = $createWebhooksAction;
        $this->configWebhooks = $configWebhooks;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        return call_user_func(
            $this->createWebhooksAction,
            $this->shopId,
            $this->configWebhooks
        );
    }
}
