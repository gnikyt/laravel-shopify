<?php namespace OhMyBrew\ShopifyApp\Traits;

trait AuthControllerTrait
{
    /**
     * Index route which displays the login page
     *
     * @return Response
     */
    public function index()
    {
        return view('shopify-app::auth.index');
    }

    /**
     * Authenticating a shop
     *
     * @return Response
     */
    public function authenticate()
    {
        // Save the Shopify domain
        session(['shopify_domain' => request('shopify_domain')]);
    }
}
