<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

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
    {
        return View::make(
            'shopify-app::home.index',
            ['shop' => $request->user()]
        );
    }
}
