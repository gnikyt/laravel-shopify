<?php

namespace Osiset\ShopifyApp\Actions;

use Illuminate\Support\Arr;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Util;

/**
 * Run after authentication jobs.
 */
class AfterAuthorize
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
     * @param ShopIdValue $shopId The shop ID.
     *
     * @return bool
     */
    public function __invoke(ShopIdValue $shopId): bool
    {
        /**
         * Fires the job.
         *
         * @param array      $config The job's configuration.
         * @param IShopModel $shop   The shop instance.
         *
         * @return bool
         */
        $fireJob = function (array $config, IShopModel $shop): bool {
            $job = Arr::get($config, 'job');
            if (Arr::get($config, 'inline', false)) {
                // Run this job immediately
                $job::dispatchNow($shop);
            } else {
                // Run later
                $job::dispatch($shop)
                    ->onQueue(Util::getShopifyConfig('job_queues')['after_authenticate']);
            }

            return true;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Grab the jobs config
        $jobsConfig = Util::getShopifyConfig('after_authenticate_job');
        if (Arr::has($jobsConfig, 0)) {
            // We have multi-jobs
            foreach ($jobsConfig as $jobConfig) {
                // We have a job, pass the shop object to the constructor
                $fireJob($jobConfig, $shop);
            }

            return true;
        } elseif (Arr::has($jobsConfig, 'job')) {
            // We have a single job
            return $fireJob($jobsConfig, $shop);
        }

        // We have no jobs
        return false;
    }
}
