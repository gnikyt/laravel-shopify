<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Actions\CreateWebhooks as CreateWebhooksAction;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;

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
     * @var ShopIdValue
     */
    protected $shopId;

    /**
     * The webhooks to add.
     *
     * @var array
     */
    protected $configWebhooks;

    /**
     * Create a new job instance.
     *
     * @param ShopIdValue $shopId         The shop ID.
     * @param array       $configWebhooks The webhooks to add.
     *
     * @return void
     */
    public function __construct(ShopIdValue $shopId, array $configWebhooks)
    {
        $this->shopId = $shopId;
        $this->configWebhooks = $configWebhooks;
    }

    /**
     * Execute the job.
     *
     * @param CreateWebhooksAction $createWebhooksAction The action for creating webhooks.
     *
     * @return array
     */
    public function handle(CreateWebhooksAction $createWebhooksAction): array
    {
        return call_user_func(
            $createWebhooksAction,
            $this->shopId,
            $this->configWebhooks
        );
    }
}
