<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;

/**
 * Attempt to install script tags on a shop.
 */
class DispatchScriptsAction
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
     * @param int  $shopId The shop ID.
     * @param bool $inline Fire the job inlin e (now) or queue.
     *
     * @return bool
     */
    public function __invoke(int $shopId, bool $inline = false): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the scripttags
        $scripttags = Config::get('shopify-app.scripttags');
        if (count($scripttags) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        if ($inline) {
            ScripttagInstaller::dispatchNow($shop, $scripttags);
        } else {
            ScripttagInstaller::dispatch($shop, $scripttags)
                ->onQueue(Config::get('shopify-app.job_queues.scripttags'));
        }

        return true;
    }
}
