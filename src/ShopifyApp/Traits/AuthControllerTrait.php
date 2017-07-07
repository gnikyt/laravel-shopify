<?php namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Http\Request;

trait AuthControllerTrait
{
    /**
     * Index route which displays the login page
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        return view('shopify-app::auth.index');
    }

    /**
     * Authenticating a shop
     *
     * @param Request $request
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {
        // Save the Shopify domain
        $request->session()->put('shopify_domain', $request->input('shopify_domain'));
    }
}
