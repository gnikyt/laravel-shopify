<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * The auth shop handler.
     *
     * @var AuthShopHandler
     */
    protected $authShopHandler;

    /**
     * The shop session handler.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Setup.
     *
     * @param IShopQuery      $shopQuery       The querier for the shop.
     * @param AuthShopHandler $authShopHandler The auth shop handler.
     * @param ShopSession     $shopSession     The shop session handler.
     *
     * @return self
     */
    public function __construct(
        IShopQuery $shopQuery,
        AuthShopHandler $authShopHandler,
        ShopSession $shopSession
    ) {
        $this->shopQuery = $shopQuery;
        $this->authShopHandler = $authShopHandler;
        $this->shopSession = $shopSession;
    }

    /**
     * Execution.
     *
     * @param string $shopDomain The shop's domain.
     * @param string $code       The code from Shopify.
     *
     * @return object|ModelNotFoundException
     */
    public function __invoke(string $shopDomain, string $code): object
    {
        // Get the shop
        $shopDomain = ShopifyApp::sanitizeShopDomain($shopDomain);
        $shop = $this->shopQuery->getByDomain($shopDomain);
        if (!$shop) {
            throw new ModelNotFoundException("Unable to find record for {$shopDomain}");
        }

        // Start the process
        $auth = $this->authShopHandler->setShop($shop);
        if (empty($code)) {
            // We need the code first
            $authUrl = $auth->buildAuthUrl(
                $shop->hasOfflineAccess() ?
                    Config::get('shopify-app.api_grant_mode') :
                    $this->shopSession::GRANT_OFFLINE
            );

            // Call the partial callback with the shop and auth URL as params
            return (object) [
                'completed' => false,
                'url'       => $authUrl,
            ];
        }

        // We have a good code, get the access details
        $access = $auth->getAccess($code);
        $session = $this->shopSession->setShop($shop);
        $session->setDomain($shopDomain);
        $session->setAccess($access);

        // Do post processing and dispatch the jobs
        $auth->postProcess();
        $auth->dispatchJobs();

        // Dispatch the events
        $auth->dispatchEvent();

        return (object) [
            'completed' => true,
        ];
    }
}
