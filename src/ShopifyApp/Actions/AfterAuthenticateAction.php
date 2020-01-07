<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;

/**
 * Run after authentication jobs.
 */
class AfterAuthenticateAction
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
     *
     * @param string $shopDomain The shop's domain.
     *
     * @return bool
     */
    public function __invoke(string $shopDomain): bool
    {
        /**
         * Fires the job.
         *
         * @param array $config The job's configuration
         *
         * @return bool
         */
        $fireJob = function ($config): bool {
            $job = $config['job'];
            if (isset($config['inline']) && $config['inline'] === true) {
                // Run this job immediately
                $job::dispatchNow($this->shop);
            } else {
                // Run later
                $job::dispatch($this->shop)
                    ->onQueue(Config::get('shopify-app.job_queues.after_authenticate'));
            }

            return true;
        };

        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // Grab the jobs config
        $jobsConfig = Config::get('shopify-app.after_authenticate_job');

        // We have multi-jobs
        if (isset($jobsConfig[0])) {
            foreach ($jobsConfig as $jobConfig) {
                // We have a job, pass the shop object to the contructor
                $fireJob($jobConfig);
            }

            return true;
        }

        // We have a single job
        if (isset($jobsConfig['job'])) {
            return $fireJob($jobsConfig);
        }

        return false;
    }
}
