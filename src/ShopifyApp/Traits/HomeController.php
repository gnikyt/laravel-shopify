<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Contracts\View\View as ViewView;

/**
 * Responsible for showing the main homescreen for the app.
 */
trait HomeController
{
    /**
     * Index route which displays the home page of the app.
     *
     * @param Request $request The request object.
     *
     * @return ViewView
     */
    public function index(Request $request): ViewView
    {print_r($request->user());exit;
        return View::make('shopify-app::home.index');
    }
}
