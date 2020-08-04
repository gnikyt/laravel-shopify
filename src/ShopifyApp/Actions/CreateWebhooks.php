<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Traits\ConfigAccessible;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * Create webhooks for this app on the shop.
 */
class CreateWebhooks
{
    use ConfigAccessible;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopId $shopId         The shop ID.
     * @param array  $configWebhooks The webhooks to add.
     *
     * @return array
     */
    public function __invoke(ShopId $shopId, array $configWebhooks): array
    {
        /**
         * Checks if a webhooks exists already in the shop.
         *
         * @param array $webhook  The webhook config.
         * @param ResponseAccess $webhooks The current webhooks to search.
         *
         * @return bool
         */
        $exists = function (array $webhook, ResponseAccess $webhooks): bool {
            foreach ($webhooks as $shopWebhook) {
                if ($shopWebhook['address'] === $webhook['address']) {
                    // Found the webhook in our list
                    return true;
                }
            }

            return false;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);
        $apiHelper = $shop->apiHelper();

        // Get the webhooks existing in for the shop
        $webhooks = $apiHelper->getWebhooks();

        $created = [];
        $deleted = [];
        $used = [];
        foreach ($configWebhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$exists($webhook, $webhooks)) {
                // It does not... create the webhook
                $apiHelper->createWebhook($webhook);
                $created[] = $webhook;
            }

            $used[] = $webhook['address'];
        }

        // Delete unused webhooks
        foreach ($webhooks as $webhook) {
            if (!in_array($webhook->address, $used)) {
                // Webhook should be deleted
                $apiHelper->deleteWebhook($webhook->id);
                $deleted[] = $webhook;
            }
        }

        return [
            'created' => $created,
            'deleted' => $deleted,
        ];
    }
}
