<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Requests\AuthShop;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use Illuminate\Contracts\View\View as ViewContract;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShop;

/**
 * Responsible for authenticating the shop.
 */
trait AuthControllerTrait
{
    /**
     * Index route which displays the login page.
     * 
     * @param Request $request The HTTP request.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return View::make(
            'shopify-app::auth.index',
            [
                'shopDomain' => $request->query('shop'),
            ]
        );
    }

    /**
     * Authenticating a shop.
     *
     * @param AuthShop $request The incoming request.
     *
     * @return ViewContract|RedirectResponse
     */
    public function authenticate(AuthShop $request, AuthenticateShop $authShop)
    {
        // Run the action
        $validated = $request->validated();
        $result = $authShop($validated['shop'], $validated['code']);
        if ($result->completed) {
            $return_to = Session::get('return_to');
            if ($return_to) {
                Session::forget('return_to');
                return Redirect::to($return_to);
            }

            // No return_to, go to home route
            return Redirect::route('home');
        }

        return View::make(
            'shopify-app::auth.fullpage_redirect',
            [
                'authUrl'    => $result->url,
                'shopDomain' => $validated['shop'],
            ]
        );
    }
}
