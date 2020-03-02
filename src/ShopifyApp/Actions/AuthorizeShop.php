<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

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
     * @return self
     */
    public function __construct(
        IShopQuery $shopQuery,
        ShopSession $shopSession
    ) {
        $this->shopQuery = $shopQuery;
        $this->shopSession = $shopSession;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopDomain  $shopDomain The shop ID.
     * @param string|null $code       The code from Shopify.
     *
     * @return object
     */
    public function __invoke(ShopDomain $shopDomain, ?string $code): object
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($shopDomain);
        $apiHelper = $shop->apiHelper();

        // Return data
        $return = [
            'completed' => false,
            'url'       => null,
        ];

        // Start the process
        if (empty($code)) {
            // We need the code first
            $authUrl = $apiHelper->buildAuthUrl(
                $shop->hasOfflineAccess() ?
                    AuthMode::fromNative($this->getConfig('api_grant_mode')) :
                    AuthMode::OFFLINE(),
                $this->getConfig('api_scopes')
            );

            // Call the partial callback with the shop and auth URL as params
            $return['url'] = $authUrl;
        } else {
            // We have a good code, get the access details
            $session = $this->shopSession->make($shop->getDomain());
            $session->setAccess($apiHelper->getAccessData($code));

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
