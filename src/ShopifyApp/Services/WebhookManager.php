<?php

namespace OhMyBrew\ShopifyApp\Services;

use OhMyBrew\ShopifyApp\Models\Shop;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

/**
 * Responsible for managing webhooks.
 */
class WebhookManager
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The shop object.
     *
     * @var IShopModel
     */
    protected $shop;

    /**
     * Cached shop webhooks result.
     *
     * @var array
     */
    protected $shopWebhooks;

    /**
     * Create a new job instance.
     *
     * @param IShopModel $shop The shop object
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopModel $shop)
    {
        $this->apiHelper = $apiHelper;
        $this->shop = $shop;
    }

    /**
     * Gets the webhooks present in the shop.
     *
     * @return array
     */
    public function shopWebhooks(): array
    {
        if (!$this->shopWebhooks) {
            $this->shopWebhooks = $this->apiHelper->setInstance($this->shop->api())->getWebhooks();
        }

        return $this->shopWebhooks;
    }

    /**
     * Gets the webhooks present in the configuration.
     *
     * @return array
     */
    public function configWebhooks()
    {
        return Config::get('shopify-app.webhooks');
    }

    /**
     * Check if webhook is in the shop (by address).
     *
     * @param array $webhook The webhook
     *
     * @return bool
     */
    public function webhookExists(array $webhook): bool
    {
        $shopWebhooks = $this->shopWebhooks();
        foreach ($shopWebhooks as $shopWebhook) {
            if ($shopWebhook->address === $webhook['address']) {
                // Found the webhook in our list
                return true;
            }
        }

        return false;
    }

    /**
     * Creates webhooks (if they do not exist).
     *
     * @return array
     */
    public function createWebhooks(): array
    {
        $configWebhooks = $this->configWebhooks();

        // Setup the API instance
        $api = $this->apiHelper->setInstance($this->shop->api());

        // Create if it does not exist
        $created = [];
        foreach ($configWebhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$this->webhookExists($webhook)) {
                // It does not... create the webhook
                $api->createWebhook($webhook);
                $created[] = $webhook;
            }
        }

        return $created;
    }

    /**
     * Deletes webhooks in the shop tied to the app.
     *
     * @return array
     */
    public function deleteWebhooks(): array
    {
        $shopWebhooks = $this->shopWebhooks();

        // Setup the API instance
        $api = $this->apiHelper->setInstance($this->shop->api());

        $deleted = [];
        foreach ($shopWebhooks as $webhook) {
            // Its a webhook in the config, delete it
            $api->deleteWebhook($webhook->id);
            $deleted[] = $webhook;
        }

        // Reset
        $this->shopWebhooks = null;

        return $deleted;
    }

    /**
     * Recreates the webhooks.
     *
     * @return void
     */
    public function recreateWebhooks(): void
    {
        $this->deleteWebhooks();
        $this->createWebhooks();
    }
}
