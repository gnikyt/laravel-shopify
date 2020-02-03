<?php

namespace OhMyBrew\ShopifyApp\Storage\Observers;

use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Responsible for observing changes to the shop (user) model.
 */
class Shop
{
    use ConfigAccessible;

    /**
     * The commands for shop.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * Constructor.
     *
     * @param IShopCommand $shopCommand The commands for shop.
     *
     * @return self
     */
    public function __construct(IShopCommand $shopCommand)
    {
        $this->shopCommand = $shopCommand;
    }

    /**
     * Listen to the shop creating event.
     * TODO: Move partial to command.
     *
     * @param IShopModel $shop An instance of a shop.
     *
     * @return void
     */
    public function creating(IShopModel $shop): void
    {
        $namespace = $this->getConfig('namespace');
        $freemium = $this->getConfig('billing_freemium_enabled');

        if (!empty($namespace) && !isset($shop->shopify_namespace)) {
            // Automatically add the current namespace to new records
            $this->shopCommand->setNamespaceByRef($shop, $namespace);
        }

        if ($freemium === true && !isset($shop->shopify_freemium)) {
            // Add the freemium flag to the shop
            $this->shopCommand->setAsFreemium($shop);
        }
    }
}
