<?php

namespace Osiset\ShopifyApp\Actions;

use stdClass;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Traits\ConfigAccessible;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthorizeShop
{
    use ConfigAccessible;

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
        $apiHelper = $shop->apiHelper();

        // Return data
        $return = [
            'completed' => false,
            'url'       => null,
        ];

        // Start the process
        if (empty($code)) {
            // Access/grant mode
            $grantMode = $shop->hasOfflineAccess() ?
                AuthMode::fromNative($this->getConfig('api_grant_mode')) :
                AuthMode::OFFLINE();

            // Call the partial callback with the shop and auth URL as params
            $return['url'] = $apiHelper->buildAuthUrl($grantMode, $this->getConfig('api_scopes'));
        } else {
            // if the store has been deleted, restore the store to set the access token
            if ($shop->trashed()) {
                $shop->restore();
            }

            // We have a good code, get the access details
            $this->shopSession->make($shop->getDomain());
            $this->shopSession->setAccess($apiHelper->getAccessData($code));

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
