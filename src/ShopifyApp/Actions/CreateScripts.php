<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * Create scripttags for this app on the shop.
 */
class CreateScripts
{
    use ConfigAccessible;

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
     * @param ShopId $shopId        The shop ID.
     * @param array  $configScripts The scripts to add.
     *
     * @return array
     */
    public function __invoke(ShopId $shopId, array $configScripts): array
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
        $apiHelper = $shop->apiHelper();

        // Get the scripts existing in for the shop
        $scripts = $apiHelper->getScriptTags();

        // Keep track of whats created
        $created = [];
        foreach ($configScripts as $scripttag) {
            // Check if the required scripttag exists on the shop
            if (!$exists($scripttag, $scripts)) {
                // It does not... create the scripttag
                $apiHelper->createScriptTag($scripttag);

                // Keep track of what was created
                $created[] = $scripttag;
            }
        }

        return $created;
    }
}
