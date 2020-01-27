<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Delete webhooks for this app on the shop.
 */
class DeleteWebhooks
{
    /**
     * The API helper.
     *
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param ApiHelper $apiHelper The API helper.
     * @param ShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(ApiHelper $apiHelper, ShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
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

        // Set the API instance
        $this->apiHelper->setInstance($shop->api());

        // Get the webhooks
        $webhooks = $this->apiHelper->getWebhooks();

        $deleted = [];
        foreach ($webhooks as $webhook) {
            // Its a webhook in the config, delete it
            $this->api->deleteWebhook($webhook->id);

            // Keep track of what was deleted
            $deleted[] = $webhook;
        }

        return $deleted;
    }
}
