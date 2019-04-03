<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Requests\AuthShop;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Services\ShopSession;

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
        $shop = ShopifyApp::shop($shopDomain);

        // Start the process
        $auth = new AuthShopHandler($shop);
        $session = new ShopSession($shop);

        // Check if we have a code
        if (!$request->has('code')) {
            // Handle a request without a code, do a fullpage redirect
            $authUrl = $auth->buildAuthUrl();

            return View::make('shopify-app::auth.fullpage_redirect', compact('authUrl', 'shopDomain'));
        }

        // We have a good code, do post processing, and run the jobs
        $access = $auth->getAccess($validated['code']);
        $auth->postProcess();
        $auth->dispatchJobs($session);

        // Save the session
        $session->setDomain($shopDomain);
        $session->setAccess($access);

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
