<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Requests\AuthShop;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;

/**
 * Responsible for authenticating the shop.
 */
trait AuthControllerTrait
{
    /**
     * Index route which displays the login page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $shopDomain = Request::query('shop');

        return View::make('shopify-app::auth.index', compact('shopDomain'));
    }

    /**
     * Authenticating a shop.
     *
     * @param \OhMyBrew\ShopifyApp\Requests\AuthShop $request
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function authenticate(AuthShop $request)
    {
        // Get the validated data
        $validated = $request->validated();
        $shopDomain = ShopifyApp::sanitizeShopDomain($validated['shop']);

        // Start the process
        $authHandler = new AuthShopHandler($shopDomain);
        $authHandler->storeSession();

        if (!$request->has('code')) {
            // Handle a request without a code, do a fullpage redirect
            $authUrl = $authHandler->buildAuthUrl();

            return View::make('shopify-app::auth.fullpage_redirect', compact('authUrl', 'shopDomain'));
        }

        // We have a good code, authenticate
        $authHandler->storeAccessToken($validated['code']);
        $authHandler->dispatchJobs();

        // Go to homepage of app or the return_to
        return $this->returnTo();
    }

    /**
     * Determines where to redirect after successfull auth.
     *
     * @return string
     */
    protected function returnTo()
    {
        // Set in AuthShop middleware
        $return_to = Session::get('return_to');
        if ($return_to) {
            Session::forget('return_to');

            return Redirect::to($return_to);
        }

        // No return_to, go to home route
        return Redirect::route('home');
    }
}
