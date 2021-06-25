<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Util;

/**
 * Attempt to install webhooks on a shop.
 */
class DispatchWebhooks
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The job to dispatch.
     *
     * @var string
     */
    protected $jobClass;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for the shop.
     * @param string     $jobClass  The job to dispatch.
     *
     * @return void
     */
    public function __construct(IShopQuery $shopQuery, string $jobClass)
    {
        $this->shopQuery = $shopQuery;
        $this->jobClass = $jobClass;
    }

    /**
     * Execution.
     *
     * @param ShopIdValue $shopId The shop ID.
     * @param bool        $inline Fire the job inlin e (now) or queue.
     *
     * @return bool
     */
    public function __invoke(ShopIdValue $shopId, bool $inline = false): bool
    {
        // Get the webhooks
        $webhooks = Util::getShopifyConfig('webhooks');
        if (count($webhooks) === 0) {
            // Nothing to do
            return false;
        }

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Run the installer job
        if ($inline) {
            ($this->jobClass)::dispatchNow(
                $shop->getId(),
                $webhooks
            );
        } else {
            ($this->jobClass)::dispatch(
                $shop->getId(),
                $webhooks
            )->onQueue(Util::getShopifyConfig('job_queues')['webhooks']);
        }

        return true;
    }
}
