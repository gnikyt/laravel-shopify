<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook job responsible for handling installation of webhook listeners.
 */
class WebhookInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop object.
     *
     * @var object
     */
    protected $shop;

    /**
     * Webhooks list.
     *
     * @var array
     */
    protected $webhooks;

    /**
     * Create a new job instance.
     *
     * @param object $shop     The shop object
     * @param array  $webhooks The webhook list
     *
     * @return void
     */
    public function __construct($shop, array $webhooks)
    {
        $this->shop = $shop;
        $this->webhooks = $webhooks;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        // Keep track of whats created
        $created = [];

        // Get the current webhooks installed on the shop
        $api = $this->shop->api();
        $shopWebhooks = $api->rest(
            'GET',
            '/admin/webhooks.json',
            [
                'limit'  => 250,
                'fields' => 'id,address',
            ]
        )->body->webhooks;

        foreach ($this->webhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$this->webhookExists($shopWebhooks, $webhook)) {
                // It does not... create the webhook
                $api->rest('POST', '/admin/webhooks.json', ['webhook' => $webhook]);
                $created[] = $webhook;
            }
        }

        return $created;
    }

    /**
     * Check if webhook is in the list.
     *
     * @param array $shopWebhooks The webhooks installed on the shop
     * @param array $webhook      The webhook
     *
     * @return bool
     */
    protected function webhookExists(array $shopWebhooks, array $webhook)
    {
        foreach ($shopWebhooks as $shopWebhook) {
            if ($shopWebhook->address === $webhook['address']) {
                // Found the webhook in our list
                return true;
            }
        }

        return false;
    }
}
