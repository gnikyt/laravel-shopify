<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthenticateShop
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The shop session handler.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Setup.
     *
     * @param IApiHelper  $apiHelper   The API helper.
     * @param IShopQuery  $shopQuery   The querier for the shop.
     * @param ShopSession $shopSession The shop session handler.
     *
     * @return self
     */
    public function __construct(
        IApiHelper $apiHelper,
        IShopQuery $shopQuery,
        ShopSession $shopSession
    ) {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
        $this->shopSession = $shopSession;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopDomain $shopDomain The shop ID.
     * @param string     $code   The code from Shopify.
     *
     * @return object
     */
    public function __invoke(ShopDomain $shopDomain, string $code): object
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($shopDomain);
        $this->apiHelper->setInstance($shop->api());

        // Return data
        $return = [
            'completed' => false,
            'url'       => null,
        ];

        // Start the process
        if (empty($code)) {
            // We need the code first
            $authUrl = $this->apiHelper->buildAuthUrl(
                $shop->hasOfflineAccess() ? Config::get('shopify-app.api_grant_mode') : AuthMode::OFFLINE()->toNative(),
                Config::get('shopify-app.api_scopes')
            );

            // Call the partial callback with the shop and auth URL as params
            $return['url'] = $authUrl;
        } else {
            // We have a good code, get the access details
            $this
                ->shopSession
                ->setShop($shop)
                ->setDomain(new ShopDomain($shop->shopify_domain))
                ->setAccess(
                    $this->apiHelper->getAccessData($code)
                );

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
