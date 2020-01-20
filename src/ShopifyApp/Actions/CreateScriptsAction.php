<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Create scripttags for this app on the shop.
 */
class CreateScriptsAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IApiHelper $apiHelper The API helper.
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param int $shopId The shop ID.
     *
     * @return array
     */
    public function __invoke(int $shopId): array
    {
        /**
         * Checks if a scripttag exists already in the shop.
         *
         * @param array $script  The scripttag config.
         * @param array $scripts The current scripttags to search.
         *
         * @return bool
         */
        $exists = function (array $script, array $scripts): bool {
            foreach ($scripts as $shopScript) {
                if ($shopScript->src === $script['src']) {
                    // Found the scripttag in our list
                    return true;
                }
            }

            return false;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Set the API instance
        $this->apiHelper->setInstance($shop->api());

        // Get the scripttags in config
        $configScripts = Config::get('shopify-app.scripttags');

        // Get the scripts existing in for the shop
        $scripts = $this->apiHelper->getScriptTags();

        // Keep track of whats created
        $created = [];
        foreach ($configScripts as $scripttag) {
            // Check if the required scripttag exists on the shop
            if (!$exists($scripttag, $scripts)) {
                // It does not... create the scripttag
                $this->apiHelper->createScriptTag($scripttag);

                // Keep track of what was created
                $created[] = $scripttag;
            }
        }

        return $created;
    }
}
