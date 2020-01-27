<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthenticateShopAction
{
    /**
     * Querier for shops.
     *
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * The API helper.
     *
     * @var ApiHelper
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
     * @param ApiHelper  $apiHelper   The API helper.
     * @param ShopQuery  $shopQuery   The querier for the shop.
     * @param ShopSession $shopSession The shop session handler.
     *
     * @return self
     */
    public function __construct(
        ApiHelper $apiHelper,
        ShopQuery $shopQuery,
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
     * @param ShopId $shopId The shop ID.
     * @param string $code   The code from Shopify.
     *
     * @return object
     */
    public function __invoke(ShopId $shopId, string $code): object
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);
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
                ->setDomain($shop->shopify_domain)
                ->setAccess(
                    $this->apiHelper->getAccessData($code)
                );

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
