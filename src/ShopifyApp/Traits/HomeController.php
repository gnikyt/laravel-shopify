<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Support\Facades\View;

/**
 * Responsible for showing the main homescreen for the app.
 */
trait HomeController
{
    /**
     * Index route which displays the home page of the app.
     *
     * @return ViewView
     */
    public function index(): ViewView
    {
        return View::make('shopify-app::home.index');
    }
}
