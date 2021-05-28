<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * Create webhooks for this app on the shop.
 */
class CreateWebhooks
{
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
     * @return void
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopIdValue $shopId         The shop ID.
     * @param array       $configWebhooks The webhooks to add.
     *
     * @return array
     */
    public function __invoke(ShopIdValue $shopId, array $configWebhooks): array
    {
        /**
         * Checks if a webhooks exists already in the shop.
         *
         * @param array $webhook  The webhook config.
         * @param array $webhooks The current webhooks to search.
         *
         * @return bool
         */
        $exists = static function (array $webhook, array $webhooks): bool {
            foreach ($webhooks as $shopWebhook) {
                if ($shopWebhook['node']['endpoint']['callbackUrl'] === $webhook['address']) {
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
            if (! $exists($webhook, $webhooks['container']['edges'])) {
                // It does not... create the webhook
                $apiHelper->createWebhook($webhook);
                $created[] = $webhook;
            }

            $used[] = $webhook['address'];
        }

        // Delete unused webhooks
        foreach ($webhooks['container']['edges'] as $webhook) {
            if (! in_array($webhook['node']['endpoint']['callbackUrl'], $used)) {
                // Webhook should be deleted
                $apiHelper->deleteWebhook($webhook['node']['id']);
                $deleted[] = $webhook;
            }
        }

        return [
            'created' => $created,
            'deleted' => $deleted,
        ];
    }
}
