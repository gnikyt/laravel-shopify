<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Actions\AuthorizeShop;
use Osiset\ShopifyApp\Actions\AfterAuthorize;
use Osiset\ShopifyApp\Actions\DispatchScripts;
use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Support\Facades\Auth;
use Osiset\ShopifyApp\Actions\DispatchWebhooks;
use Osiset\ShopifyApp\Http\Requests\AuthShopify;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

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
     * @param AuthShopify      $request          The incoming request.
     * @param AuthorizeShop    $authShop         The action for authenticating a shop.
     * @param DispatchScripts  $dispatchScripts  The action for dispatching scripttag installation.
     * @param DispatchWebhooks $dispatchWebhooks The action for dispatching webhook installation.
     * @param AfterAuthorize   $afterAuthorize   The action for dispatching custom actions after authentication.
     *
     * @return ViewView|RedirectResponse
     */
    public function authenticate(
        AuthShopify $request,
        AuthorizeShop $authShop,
        DispatchScripts $dispatchScripts,
        DispatchWebhooks $dispatchWebhooks,
        AfterAuthorize $afterAuthorize
    ) {
        // Run the action
        $validated = $request->validated();
        $shopDomain = new ShopDomain($validated['shop']);
        $result = $authShop($shopDomain, isset($validated['code']) ? $validated['code'] : null);

        if ($result->completed) {
            // All good, handle the redirect
            return $this->authenticateSuccess(
                $dispatchScripts,
                $dispatchWebhooks,
                $afterAuthorize
            );
        }

        // No code, redirect to auth URL
        return $this->authenticateFail(
            $result->url,
            $shopDomain
        );
    }

    /**
     * Handles when authentication is successful.
     *
     * @param DispatchScripts  $dispatchScripts  The action for dispatching scripttag installation.
     * @param DispatchWebhooks $dispatchWebhooks The action for dispatching webhook installation.
     * @param AfterAuthorize   $afterAuthorize   The action for dispatching custom actions after authentication.
     *
     * @return RedirectResponse
     */
    protected function authenticateSuccess(
        DispatchScripts $dispatchScripts,
        DispatchWebhooks $dispatchWebhooks,
        AfterAuthorize $afterAuthorize
    ): RedirectResponse {
        // Fire the post processing jobs
        $shopId = Auth::user()->getId();
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
     * Handles when authentication is unsuccessful.
     *
     * @param string     $authUrl    The auth URl to redirect the user to get the code.
     * @param ShopDomain $shopDomain The shop's domain.
     *
     * @return ViewView
     */
    protected function authenticateFail(string $authUrl, ShopDomain $shopDomain): ViewView
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
