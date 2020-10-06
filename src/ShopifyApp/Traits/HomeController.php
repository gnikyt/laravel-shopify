<?php

namespace Osiset\ShopifyApp\Traits;

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
        if (config('shopify-app.jwt_authentication_enabled')) {
            return View::make('shopify-app::layouts.spa');
        }

        return View::make('shopify-app::home.index');
    }
}
