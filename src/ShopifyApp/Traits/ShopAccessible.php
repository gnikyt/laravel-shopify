<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

/**
 * Allows for setting of a shop and accessing it.
 */
trait ShopAccessibleTrait
{
    /**
     * The shop.
     *
     * @var IShopModel
     */
    protected $shop;

    /**
     * Sets the shop.
     *
     * @param IShopModel $shop The shop.
     *
     * @return self
     */
    public function setShop(IShopModel $shop): self
    {
        $this->shop = $shop;

        return $this;
    }
}
