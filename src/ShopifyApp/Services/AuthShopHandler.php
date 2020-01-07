<?php

namespace OhMyBrew\ShopifyApp\Services;

use stdClass;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Models\Shop;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Events\AppLoggedIn;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;

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
     * Sets the shop.
     *
     * @param IShopModel $shop The shop.
     *
     * @return self
     */
    public function setShop(IShopModel $shop)
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
     * @param string|null $mode The mode of grant ("offline"/"per-user").
     *
     * @return string
     */
    public function buildAuthUrl($mode = null)
    {
        // Determine the type of mode
        // Grab the authentication URL
        return $this->api->getAuthUrl(
            Config::get('shopify-app.api_scopes'),
            URL::secure(Config::get('shopify-app.api_redirect')),
            $mode ?? 'offline'
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
    /* MARK: REPLACED WITH RESTORESHOPACTION */
    public function postProcess()
    {
        if (!$this->shop->trashed()) {
            return;
        }

        // Trashed, fix it
        $this->shop->restore();
        $this->shop->charges()->restore();
        $this->shop->save();
    }

    /**
     * Dispatches event on login.
     *
     * @return void
     */
    public function dispatchEvent()
    {
        // Fire event to tell outside that the merchant logged in
        Event::dispatch(new AppLoggedIn($this->shop));
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
    /* MARK: REPLACED WITH INSTALLWEBHOOKSACTION */
    public function dispatchWebhooks()
    {
        $webhooks = Config::get('shopify-app.webhooks');
        if (count($webhooks) > 0) {
            WebhookInstaller::dispatch($this->shop)
                ->onQueue(Config::get('shopify-app.job_queues.webhooks'));
        }
    }

    /**
     * Dispatches the job to install scripttags.
     *
     * @return void
     */
    /* MARK: REPLACED WITH INSTALLSCRIPTSACTION */
    public function dispatchScripttags()
    {
        $scripttags = Config::get('shopify-app.scripttags');
        if (count($scripttags) > 0) {
            ScripttagInstaller::dispatch($this->shop, $scripttags)
                ->onQueue(Config::get('shopify-app.job_queues.scripttags'));
        }
    }

    /**
     * Dispatches the after authenticate job, if any.
     *
     * @return void
     */
    /* MARK: REPLACED WITH RUNAFTERAUTHACTION */
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
                $job::dispatch($this->shop)
                    ->onQueue(Config::get('shopify-app.job_queues.after_authenticate'));
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
