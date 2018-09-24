<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;

trait AuthControllerTrait
{
    /**
     * Index route which displays the login page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('shopify-app::auth.index', ['shopDomain' => request()->query('shop')]);
    }

    /**
     * Authenticating a shop.
     *
     * @return \Illuminate\Http\Response
     */
    public function authenticate()
    {
        // Grab the shop domain (uses session if redirected from middleware)
        $shopDomain = request('shop');
        if (!$shopDomain) {
            // Back to login, no shop
            return redirect()->route('login');
        }

        // Save shop domain to session
        config(['session.expire_on_close' => true]);
        session(['shopify_domain' => ShopifyApp::sanitizeShopDomain($shopDomain)]);

        if (!request('code')) {
            // Handle a request without a code
            return $this->authenticationWithoutCode();
        } else {
            // Handle a request with a code
            return $this->authenticationWithCode();
        }
    }

    /**
     * Fires when there is no code on authentication.
     *
     * @return \Illuminate\Http\Response
     */
    protected function authenticationWithoutCode()
    {
        // Setup an API instance
        $shopDomain = session('shopify_domain');
        $api = ShopifyApp::api();
        $api->setShop($shopDomain);

        // Grab the authentication URL
        $authUrl = $api->getAuthUrl(
            config('shopify-app.api_scopes'),
            secure_url(config('shopify-app.api_redirect'))
        );

        // Do a fullpage redirect
        return view('shopify-app::auth.fullpage_redirect', [
            'authUrl'    => $authUrl,
            'shopDomain' => $shopDomain,
        ]);
    }

    /**
     * Fires when there is a code on authentication.
     *
     * @return \Illuminate\Http\Response
     */
    protected function authenticationWithCode()
    {
        // Setup an API instance
        $shopDomain = session('shopify_domain');
        $api = ShopifyApp::api();
        $api->setShop($shopDomain);

        // Check if request is verified
        if (!$api->verifyRequest(request()->all())) {
            // Not valid, redirect to login and show the errors
            return redirect()->route('login')->with('error', 'Invalid signature');
        }

        // Grab the shop; restore if need-be
        $shop = ShopifyApp::shop();
        if ($shop->trashed()) {
            $shop->restore();
            $shop->charges()->restore();
        }

        // Save the token to the shop
        $shop->shopify_token = $api->requestAccessToken(request('code'));
        $shop->save();

        // Install webhooks and scripttags
        $this->installWebhooks();
        $this->installScripttags();

        // Run after authenticate job
        $this->afterAuthenticateJob();

        // Go to homepage of app or the return_to
        return $this->returnTo();
    }

    /**
     * Installs webhooks (if any).
     *
     * @return void
     */
    protected function installWebhooks()
    {
        $webhooks = config('shopify-app.webhooks');
        if (count($webhooks) > 0) {
            dispatch(
                new WebhookInstaller(ShopifyApp::shop(), $webhooks)
            );
        }
    }

    /**
     * Installs scripttags (if any).
     *
     * @return void
     */
    protected function installScripttags()
    {
        $scripttags = config('shopify-app.scripttags');
        if (count($scripttags) > 0) {
            dispatch(
                new ScripttagInstaller(ShopifyApp::shop(), $scripttags)
            );
        }
    }

    /**
     * Runs a job after authentication, if provided.
     *
     * @return bool
     */
    protected function afterAuthenticateJob()
    {
        // Grab the shop to use in the job and the jobs config
        $shop = ShopifyApp::shop();
        $jobsConfig = config('shopify-app.after_authenticate_job');

        /**
         * Fires the job.
         *
         * @param array $config The job's configuration
         *
         * @return bool
         */
        $fireJob = function ($config) use ($shop) {
            $job = new $config['job']($shop);
            if (isset($config['inline']) && $config['inline'] === true) {
                // Run this job immediately
                $job->handle();
            } else {
                // Run later
                dispatch($job);
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

    /**
     * Determines where to redirect after successfull auth.
     *
     * @return string
     */
    protected function returnTo()
    {
        // Set in AuthShop middleware
        $return_to = session('return_to');
        if ($return_to) {
            session()->forget('return_to');

            return redirect($return_to);
        }

        // No return_to, go to home route
        return redirect()->route('home');
    }
}
