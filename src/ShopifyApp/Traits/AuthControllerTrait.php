<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Config;
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
     * @param \OhMyBrew\ShopifyApp\Requests\AuthShop $request The incoming request.
     * @param string                                 $type    The type of auth to do (full or partial).
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function authenticate(AuthShop $request, string $type = AuthShopHandler::FLOW_FULL)
    {
        // Get the validated data
        $validated = $request->validated();
        $shopDomain = ShopifyApp::sanitizeShopDomain($validated['shop']);
        $shop = ShopifyApp::shop($shopDomain);

        // Start the process
        $auth = new AuthShopHandler($shop);
        $session = new ShopSession($shop);
        $session->setDomain($shopDomain);

        // Check if we need to do a full auth flow (most likely not)
//        if ($type === AuthShopHandler::FLOW_FULL) {
            // Check if we have a code


            // ALWAYS to a FULL REDIRECT
            if (!$request->filled('code')) {
                // Handle a request without a code, do a fullpage redirect
                // Check if they have offline access, if they do not, this is most likely an install
                // If they do, fallback to using configured grant mode
                $authUrl = $auth->buildAuthUrl(
                    $shop->hasOfflineAccess() ?
                        Config::get('shopify-app.api_grant_mode') :
                        ShopSession::GRANT_OFFLINE
                );

                return View::make(
                    'shopify-app::auth.fullpage_redirect',
                    compact('authUrl', 'shopDomain')
                );
            }

            // We have a good code, get the access details
            $access = $auth->getAccess($validated['code']);
            $session->setAccess($access);

            // Do post processing and dispatch the jobs
            $auth->postProcess();
            $auth->dispatchJobs();
//        }

        // Dispatch the events always (for full and partial)
        $auth->dispatchEvent();

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
