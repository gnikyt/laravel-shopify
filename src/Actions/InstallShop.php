<?php

namespace Osiset\ShopifyApp\Actions;

use Exception;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Values\AccessToken;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\ThemeSupportLevel;
use Osiset\ShopifyApp\Util;

/**
 * Install steps for a shop.
 */
class InstallShop
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
     * The action for verify theme support
     *
     * @var VerifyThemeSupport
     */
    protected $verifyThemeSupport;

    /**
     * Setup.
     *
     * @param IShopQuery  $shopQuery   The querier for the shop.
     * @param VerifyThemeSupport    $verifyThemeSupport     The action for verify theme support
     *
     * @return void
     */
    public function __construct(
        IShopQuery $shopQuery,
        IShopCommand $shopCommand,
        VerifyThemeSupport $verifyThemeSupport
    ) {
        $this->shopQuery = $shopQuery;
        $this->shopCommand = $shopCommand;
        $this->verifyThemeSupport = $verifyThemeSupport;
    }

    /**
     * Execution.
     *
     * @param ShopDomain  $shopDomain The shop ID.
     * @param string|null $code       The code from Shopify.
     *
     * @return array
     */
    public function __invoke(ShopDomain $shopDomain, ?string $code): array
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($shopDomain, [], true);

        if ($shop === null) {
            // Shop does not exist, make them and re-get
            $this->shopCommand->make($shopDomain, NullAccessToken::fromNative(null));
            $shop = $this->shopQuery->getByDomain($shopDomain);
        }

        // Access/grant mode
        $apiHelper = $shop->apiHelper();
        $grantMode = $shop->hasOfflineAccess() ?
            AuthMode::fromNative(Util::getShopifyConfig('api_grant_mode', $shop)) :
            AuthMode::OFFLINE();

        // If there's no code
        if (empty($code)) {
            return [
                'completed' => false,
                'url' => $apiHelper->buildAuthUrl($grantMode, Util::getShopifyConfig('api_scopes', $shop)),
                'shop_id' => $shop->getId(),
            ];
        }

        try {
            // if the store has been deleted, restore the store to set the access token
            if ($shop->trashed()) {
                $shop->restore();
            }

            // Get the data and set the access token
            $data = $apiHelper->getAccessData($code);
            $this->shopCommand->setAccessToken($shop->getId(), AccessToken::fromNative($data['access_token']));

            $themeSupportLevel = call_user_func($this->verifyThemeSupport, $shop->getId());
            $this->shopCommand->setThemeSupportLevel($shop->getId(), ThemeSupportLevel::fromNative($themeSupportLevel));

            return [
                'completed' => true,
                'url' => null,
                'shop_id' => $shop->getId(),
                'theme_support_level' => $themeSupportLevel,
            ];
        } catch (Exception $e) {
            // Just return the default setting
            return [
                'completed' => false,
                'url' => null,
                'shop_id' => null,
                'theme_support_level' => null,
            ];
        }
    }
}
