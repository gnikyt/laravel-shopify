<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Attempt to install script tags on a shop.
 */
class DispatchScripts
{
    use ConfigAccessible;

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
     * The action to handle the job.
     *
     * @var callable
     */
    protected $actionClass;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery   The querier for the shop.
     * @param string     $jobClass    The job to dispatch.
     * @param callable   $actionClass The action to handle the job.
     *
     * @return void
     */
    public function __construct(IShopQuery $shopQuery, string $jobClass, callable $actionClass)
    {
        $this->shopQuery = $shopQuery;
        $this->jobClass = $jobClass;
        $this->actionClass = $actionClass;
    }

    /**
     * Execution.
     *
     * @param ShopId $shopId   The shop ID.
     * @param bool   $inline   Fire the job inline (now) or queue.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId, bool $inline = false): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the scripttags
        $scripttags = $this->getConfig('scripttags');
        if (count($scripttags) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        if ($inline) {
            ($this->jobClass)::dispatchNow(
                $shop->getId(),
                $this->actionClass,
                $scripttags
            );
        } else {
            ($this->jobClass)::dispatch(
                $shop->getId(),
                $this->actionClass,
                $scripttags
            )->onQueue($this->getConfig('job_queues')['scripttags']);
        }

        return true;
    }
}
