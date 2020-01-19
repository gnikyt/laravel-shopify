<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OhMyBrew\ShopifyApp\Services\IApiHelper;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthenticateShopAction
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
     *
     * @param string $shopDomain The shop's domain.
     * @param string $code       The code from Shopify.
     *
     * @return object
     */
    public function __invoke(string $shopDomain, string $code): object
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));
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
                $shop->hasOfflineAccess() ?
                    Config::get('shopify-app.api_grant_mode') :
                    $this->apiHelper::MODE_OFFLINE,
                Config::get('shopify-app.api_scopes')
            );

            // Call the partial callback with the shop and auth URL as params
            $return['url'] = $authUrl;
        } else {
            // We have a good code, get the access details
            $session = $this->shopSession->setShop($shop);
            $session->setDomain($shop->shopify_domain);
            $session->setAccess($this->apiHelper->getAccessData($code));

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
