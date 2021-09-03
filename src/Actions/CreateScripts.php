<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * Create scripttags for this app on the shop.
 */
class CreateScripts
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
     * @return void
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopIdValue $shopId        The shop ID.
     * @param array       $configScripts The scripts to add.
     *
     * @return array
     */
    public function __invoke(ShopIdValue $shopId, array $configScripts): array
    {
        /**
         * Checks if a scripttag exists already in the shop.
         *
         * @param array $script  The scripttag config.
         * @param ResponseAccess $scripts The current scripttags to search.
         *
         * @return bool
         */
        $exists = function (array $script, ResponseAccess $scripts): bool {
            foreach ($scripts as $shopScript) {
                if ($shopScript['src'] === $script['src']) {
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

        // Keep track of whats created, deleted, and used
        $created = [];
        $deleted = [];
        $used = [];
        foreach ($configScripts as $scripttag) {
            // Check if the required scripttag exists on the shop
            if (! $exists($scripttag, $scripts)) {
                // It does not... create the scripttag
                $apiHelper->createScriptTag($scripttag);
                $created[] = $scripttag;
            }

            $used[] = $scripttag['src'];
        }

        // Delete unused scripttags
        foreach ($scripts as $scriptTag) {
            if (! in_array($scriptTag->src, $used)) {
                // Scripttag should be deleted
                $apiHelper->deleteScriptTag($scriptTag->id);
                $deleted[] = $scriptTag;
            }
        }

        return [
            'created' => $created,
            'deleted' => $deleted,
        ];
    }
}
