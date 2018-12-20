<?php

namespace OhMyBrew\ShopifyApp\Services;

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
     * The shop's domain.
     *
     * @var string|null
     */
    protected $shopDomain;

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
     * @param string $shopDomain The shop's domain.
     *
     * @return self
     */
    public function __construct(string $shopDomain)
    {
        $this->shopDomain = $shopDomain;
        $this->shop = ShopifyApp::shop($this->shopDomain);
        $this->api = ShopifyApp::api();
        $this->api->setShop($this->shopDomain);

        return $this;
    }

    /**
     * Start the auth setup by storing the domaion to the session.
     */
    public function storeSession()
    {
        // Save shop domain to session, set no expiry on close because Laravel defaults to it
        Config::set('session.expire_on_close', true);
        Session::put('shopify_domain', $this->shopDomain);
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
            URL::secure(Config::get('shopify-app.api_redirect'))
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
     * Finish the process by storing the new auth token.
     *
     * @param string $code The code from the request.
     *
     * @return void
     */
    public function storeAccessToken(string $code)
    {
        // Grab or create the shop; restore if need-be
        if ($this->shop->trashed()) {
            $this->shop->restore();
            $this->shop->charges()->restore();
        }

        // Save the token to the shop
        $this->shop->shopify_token = $this->api->requestAccessToken($code);
        $this->shop->save();
    }

    /**
     * Dispatches the jobs that happen after authentication.
     *
     * @return bool
     */
    public function dispatchJobs()
    {
        if (!$this->shop->shopify_token) {
            throw new Exception('Shopify access token needed to dispatch jobs.');
        }

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
            WebhookInstaller::dispatch($this->shop, $webhooks);
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
            ScripttagInstaller::dispatch($this->shop, $scripttags);
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
                $job::dispatch($this->shop);
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
