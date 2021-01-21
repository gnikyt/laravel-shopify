<?php

namespace Osiset\ShopifyApp\Storage\Observers;

use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use function Osiset\ShopifyApp\getShopifyConfig;

/**
 * Responsible for observing changes to the shop (user) model.
 */
class Shop
{
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
     * @return void
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
        $namespace = getShopifyConfig('namespace');
        $freemium = getShopifyConfig('billing_freemium_enabled');

        if (! empty($namespace) && ! isset($shop->shopify_namespace)) {
            // Automatically add the current namespace to new records
            $this->shopCommand->setNamespaceByRef($shop, $namespace);
        }

        if ($freemium === true && ! isset($shop->shopify_freemium)) {
            // Add the freemium flag to the shop
            $this->shopCommand->setAsFreemiumByRef($shop);
        }
    }
}
