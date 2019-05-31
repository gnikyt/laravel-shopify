<?php

namespace OhMyBrew\ShopifyApp\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event fired when a shop passes through authentication.
 */
class AppLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Shop's instance.
     *
     * @var string
     */
    protected $shop;

    /**
     * Create a new evebt instance.
     *
     * @param object $shop The shop.
     *
     * @return void
     */
    public function __construct($shop)
    {
        $this->shop = $shop;
    }
}
