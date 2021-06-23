<?php

namespace Osiset\ShopifyApp\Actions;

use Exception;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Util;
use stdClass;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthorizeShop
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Commander for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * The shop session handler.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Setup.
     *
     * @param IShopQuery  $shopQuery   The querier for the shop.
     * @param ShopSession $shopSession The shop session handler.
     *
     * @return void
     */
    public function __construct(
        IShopQuery $shopQuery,
        IShopCommand $shopCommand,
        ShopSession $shopSession
    ) {
        $this->shopQuery = $shopQuery;
        $this->shopCommand = $shopCommand;
        $this->shopSession = $shopSession;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopDomain  $shopDomain The shop ID.
     * @param string|null $code       The code from Shopify.
     *
     * @return stdClass
     */
    public function __invoke(ShopDomain $shopDomain, ?string $code): stdClass
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($shopDomain, [], true);
        if ($shop === null) {
            // Shop does not exist, make them and re-get
            $this->shopCommand->make($shopDomain, NullAccessToken::fromNative(null));
            $shop = $this->shopQuery->getByDomain($shopDomain);
        }

        // Return data
        $return = [
            'completed' => false,
            'url'       => null,
        ];

        $apiHelper = $shop->apiHelper();

        // Access/grant mode
        $grantMode = $shop->hasOfflineAccess() ?
            AuthMode::fromNative(Util::getShopifyConfig('api_grant_mode', $shop)) :
            AuthMode::OFFLINE();

        $return['url'] = $apiHelper->buildAuthUrl($grantMode, Util::getShopifyConfig('api_scopes', $shop));

        // If there's no code
        if (empty($code)) {
            return (object) $return;
        }

        // if the store has been deleted, restore the store to set the access token
        if ($shop->trashed()) {
            $shop->restore();
        }

        // We have a good code, get the access details
        $this->shopSession->make($shop->getDomain());

        try {
            $this->shopSession->setAccess($apiHelper->getAccessData($code));
            $return['url'] = null;
            $return['completed'] = true;
        } catch (Exception $e) {
            // Just return the default setting
        }

        return (object) $return;
    }
}
