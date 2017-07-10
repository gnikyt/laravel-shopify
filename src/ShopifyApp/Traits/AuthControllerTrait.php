<?php namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

trait AuthControllerTrait
{
    /**
     * Index route which displays the login page
     *
     * @return Response
     */
    public function index()
    {
        return view('shopify-app::auth.index');
    }

    /**
     * Authenticating a shop
     *
     * @return Response
     */
    public function authenticate()
    {
        // Save the Shopify domain
        session(['shopify_domain' => request('shopify_domain')]);

        // Install webhooks and scripttags
        $this->installWebhooks();
        $this->installScripttags();
    }

    /**
     * Installs webhooks (if any)
     *
     * @return void
     */
    protected function installWebhooks()
    {
        $webhooks = config('shopify-app.webhooks');
        if (sizeof($webhooks) > 0) {
            dispatch(
                new WebhookInstaller(ShopifyApp::shop(), $webhooks)
            );
        }
    }

    /**
     * Installs scripttags (if any)
     *
     * @return void
     */
    protected function installScripttags()
    {
        $scripttags = config('shopify-app.scripttags');
        if (sizeof($scripttags) > 0) {
            dispatch(
                new ScripttagInstaller(ShopifyApp::shop(), $scripttags)
            );
        }
    }
}
