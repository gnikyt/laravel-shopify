<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Attempt to install webhooks on a shop.
 */
class DispatchWebhooks
{
    /**
     * Querier for shops.
     *
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param ShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(ShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param ShopId $shopId The shop ID.
     * @param bool   $inline Fire the job inlin e (now) or queue.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId, bool $inline = false): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the webhooks
        $webhooks = Config::get('shopify-app.webhooks');
        if (count($webhooks) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        if ($inline) {
            WebhookInstaller::dispatchNow($shop);
        } else {
            WebhookInstaller::dispatch($shop)
                ->onQueue(Config::get('shopify-app.job_queues.webhooks'));
        }

        return true;
    }
}
