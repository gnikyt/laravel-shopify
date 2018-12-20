<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\View;

/**
 * Responsible for showing the main homescreen for the app.
 */
trait HomeControllerTrait
{
    /**
     * Index route which displays the home page of the app.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return View::make('shopify-app::home.index');
    }
}
