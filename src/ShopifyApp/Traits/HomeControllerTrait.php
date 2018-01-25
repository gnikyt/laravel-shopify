<?php

namespace OhMyBrew\ShopifyApp\Traits;

trait HomeControllerTrait
{
    /**
     * Index route which displays the home page of the app.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('shopify-app::home.index');
    }
}
