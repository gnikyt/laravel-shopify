<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Values\ShopId;

/**
 * Delete webhooks for this app on the shop.
 */
class DeleteWebhooks
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
     * @param ShopId $shopId The shop ID.
     *
     * @return array
     */
    public function __invoke(ShopId $shopId): array
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);
        $apiHelper = $shop->apiHelper();

        // Get the webhooks
        $webhooks = $apiHelper->getWebhooks();

        $deleted = [];
        foreach (data_get($webhooks, 'data.webhookSubscriptions.container.edges', []) as $webhook) {
            // Its a webhook in the config, delete it
            $apiHelper->deleteWebhook(data_get($webhook, 'node.id'));

            // Keep track of what was deleted
            $deleted[] = $webhook;
        }

        return $deleted;
    }
}
