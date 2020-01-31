<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Run after authentication jobs.
 */
class AfterAuthenticate
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
     * @param ShopId $shopId The shop ID.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId): bool
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
            $job = $config['job'];
            if (isset($config['inline']) && $config['inline'] === true) {
                // Run this job immediately
                $job::dispatchNow($shop);
            } else {
                // Run later
                $job::dispatch($shop)
                    ->onQueue(Config::get('shopify-app.job_queues.after_authenticate'));
            }

            return true;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Grab the jobs config
        $jobsConfig = Config::get('shopify-app.after_authenticate_job');

        // We have multi-jobs
        if (isset($jobsConfig[0])) {
            foreach ($jobsConfig as $jobConfig) {
                // We have a job, pass the shop object to the contructor
                $fireJob($jobConfig, $shop);
            }

            return true;
        }

        // We have a single job
        if (isset($jobsConfig['job'])) {
            return $fireJob($jobsConfig, $shop);
        }

        return false;
    }
}
