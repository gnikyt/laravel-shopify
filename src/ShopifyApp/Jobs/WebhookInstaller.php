<?php namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class WebhookInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * Webhooks list
     *
     * @var array
     */
    protected $webhooks;

    /**
     * Create a new job instance.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop     The shop object
     * @param array                            $webhooks The webhook list
     *
     * @return void
     */
    public function __construct(Shop $shop, array $webhooks)
    {
        $this->shop = $shop;
        $this->webhooks = $webhooks;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get the current webhooks installed on the shop
        $api = ShopifyApp::createApiForShop($this->shop);
        $request = $api->request('GET', '/admin/webhooks.json', ['limit' => 250, 'fields' => 'id,address']);
        $shopWebhooks = $request->body->webhooks;

        foreach ($this->webhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$this->webhookExists($shopWebhooks, $webhook)) {
                // It does not... create the webhook
                $api->request('POST', '/admin/webhooks.json', ['webhook' => $webhook]);
            }
        }
    }

    /**
     * Check if webhook is in the list.
     *
     * @param array $shopWebhooks The webhooks installed on the shop
     * @param object $webhook     The webhook object
     *
     * @return boolean
     */
    protected function webhookExists(array $shopWebhooks, $webhook)
    {
        foreach ($shopWebhooks as $shopWebhook) {
            if ($shopWebhook->address === $webhook->address) {
                // Found the webhook in our list
                return true;
            }
        }

        return false;
    }
}
