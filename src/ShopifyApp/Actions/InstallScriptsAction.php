<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;

/**
 * Attempt to install script tags on a shop.
 */
class InstallScriptsAction
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
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));
        
        // Get the scripttags
        $scripttags = Config::get('shopify-app.scripttags');
        if (count($scripttags) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        ScripttagInstaller::dispatch($this->shop, $scripttags)
            ->onQueue(Config::get('shopify-app.job_queues.scripttags'));

        return true;
    }
}
