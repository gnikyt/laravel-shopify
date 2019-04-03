<?php

namespace OhMyBrew\ShopifyApp\Services;

use stdClass;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;

/**
 * Responsible for handling how to authenticate a shop.
 */
class AuthShopHandler
{
    /**
     * The shop.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * The shop API.
     *
     * @var \OhMyBrew\BasicShopifyAPI
     */
    protected $api;

    /**
     * Constructor for auth shop handler.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop $shop The shop.
     *
     * @return self
     */
    public function __construct(shop $shop)
    {
        // Setup the API
        $this->shop = $shop;
        $this->api = ShopifyApp::api();
        $this->api->setShop($this->shop->shopify_domain);

        return $this;
    }

    /**
     * Builds the authentication URL for a shop.
     *
     * @return string
     */
    public function buildAuthUrl()
    {
        // Grab the authentication URL
        return $this->api->getAuthUrl(
            Config::get('shopify-app.api_scopes'),
            URL::secure(Config::get('shopify-app.api_redirect')),
            Config::get('shopify-app.api_grant_mode')
        );
    }

    /**
     * Determines if the request HMAC is verified.
     *
     * @param array $request The request parameters.
     *
     * @return bool
     */
    public function verifyRequest(array $request)
    {
        return $this->api->verifyRequest($request);
    }

    /**
     * Finish the process by getting the access details from the code.
     *
     * @param string $code The code from the request.
     *
     * @return stdClass
     */
    public function getAccess(string $code)
    {
        return $this->api->requestAccess($code);
    }

    /**
     * Post process actions after authentication is done.
     *
     * @return void
     */
    public function postProcess()
    {
        if ($this->shop->trashed()) {
            $this->shop->restore();
            $this->shop->charges()->restore();
            $this->shop->save();
        }
    }

    /**
     * Dispatches the jobs that happen after authentication.
     *
     * @return bool
     */
    public function dispatchJobs()
    {
        $this->dispatchWebhooks();
        $this->dispatchScripttags();
        $this->dispatchAfterAuthenticate();

        return true;
    }

    /**
     * Dispatches the job to install webhooks.
     *
     * @return void
     */
    public function dispatchWebhooks()
    {
        $webhooks = Config::get('shopify-app.webhooks');
        if (count($webhooks) > 0) {
            WebhookInstaller::dispatch($this->shop)->onQueue(Config::get('shopify-app.job_queues.webhooks'));
        }
    }

    /**
     * Dispatches the job to install scripttags.
     *
     * @return void
     */
    public function dispatchScripttags()
    {
        $scripttags = Config::get('shopify-app.scripttags');
        if (count($scripttags) > 0) {
            ScripttagInstaller::dispatch($this->shop, $scripttags)->onQueue(Config::get('shopify-app.job_queues.scripttags'));
        }
    }

    /**
     * Dispatches the after authenticate job, if any.
     *
     * @return void
     */
    public function dispatchAfterAuthenticate()
    {
        // Grab the jobs config
        $jobsConfig = Config::get('shopify-app.after_authenticate_job');

        /**
         * Fires the job.
         *
         * @param array $config The job's configuration
         *
         * @return bool
         */
        $fireJob = function ($config) {
            $job = $config['job'];
            if (isset($config['inline']) && $config['inline'] === true) {
                // Run this job immediately
                $job::dispatchNow($this->shop);
            } else {
                // Run later
                $job::dispatch($this->shop)->onQueue(Config::get('shopify-app.job_queues.after_authenticate'));
            }

            return true;
        };

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
