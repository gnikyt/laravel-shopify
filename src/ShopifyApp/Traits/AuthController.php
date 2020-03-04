<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Actions\AuthorizeShop;
use Osiset\ShopifyApp\Actions\AfterAuthorize;
use Osiset\ShopifyApp\Actions\DispatchScripts;
use Illuminate\Contracts\View\View as ViewView;
use Osiset\ShopifyApp\Actions\DispatchWebhooks;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Services\ShopSession;

/**
 * Responsible for authenticating the shop.
 */
trait AuthController
{
    /**
     * Index route which displays the login page.
     *
     * @param Request $request The HTTP request.
     *
     * @return ViewView
     */
    public function index(Request $request): ViewView
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
     * @param AuthorizeShop    $authShop         The action for authenticating a shop.
     * @param DispatchScripts  $dispatchScripts  The action for dispatching scripttag installation.
     * @param DispatchWebhooks $dispatchWebhooks The action for dispatching webhook installation.
     * @param AfterAuthorize   $afterAuthorize   The action for dispatching custom actions after authentication.
     *
     * @return ViewView|RedirectResponse
     */
    public function authenticate(
        Request $request,
        IApiHelper $apiHelper,
        ShopSession $shopSession,
        AuthorizeShop $authShop,
        DispatchScripts $dispatchScripts,
        DispatchWebhooks $dispatchWebhooks,
        AfterAuthorize $afterAuthorize
    ) {
        // Setup
        $shopDomain = new ShopDomain($request->get('shop'));
        $code = $request->get('code');

        // Run the check
        $result = $authShop($shopDomain, $code);
        if (!$result->completed) {
            // Determine if the HMAC is correct
            $apiHelper->make();
            if (!$apiHelper->verifyRequest($request->all())) {
                // Go to login, something is wrong
                return Redirect::route('login');
            }

            // No code, redirect to auth URL
            return $this->oauthFailure($result->url, $shopDomain);
        }

        // Login the shop
        $shopSession->make($shopDomain);
        $shopId = $shopSession->getShop()->getId();

        // Fire the post processing jobs
        $dispatchScripts($shopId, false);
        $dispatchWebhooks($shopId, false);
        $afterAuthorize($shopId);

        // Determine if we need to redirect back somewhere
        $return_to = Session::get('return_to');
        if ($return_to) {
            Session::forget('return_to');
            return Redirect::to($return_to);
        }

        // No return_to, go to home route
        return Redirect::route('home');
    }

    /**
     * Simply redirects to Shopify's Oauth screen.
     *
     * @param Request       $request  The request object.
     * @param AuthorizeShop $authShop The action for authenticating a shop.
     *
     * @return ViewView
     */
    public function oauth(Request $request, AuthorizeShop $authShop): ViewView
    {
        // Setup
        $shopDomain = new ShopDomain($request->get('shop'));
        $result = $authShop($shopDomain, null);

        // Redirect
        return $this->oauthFailure($result->url, $shopDomain);
    }

    /**
     * Handles when authentication is unsuccessful or new.
     *
     * @param string     $authUrl    The auth URl to redirect the user to get the code.
     * @param ShopDomain $shopDomain The shop's domain.
     *
     * @return ViewView
     */
    private function oauthFailure(string $authUrl, ShopDomain $shopDomain): ViewView
    {
        return View::make(
            'shopify-app::auth.fullpage_redirect',
            [
                'authUrl'    => $authUrl,
                'shopDomain' => $shopDomain->toNative(),
            ]
        );
    }
}
