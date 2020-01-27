<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Requests\AuthShop;

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
     * @param AuthShop $request           The incoming request.
     * @param callable $authShop          The action for authenticating a shop.
     * @param callable $dispatchScripts   The action for dispatching scripttag installation.
     * @param callable $dispatchWebhooks  The action for dispatching webhook installation.
     * @param callable $afterAuthenticate The action for dispatching custom actions after authentication.
     *
     * @return ViewView|RedirectResponse
     */
    public function authenticate(
        AuthShop $request,
        callable $authShop,
        callable $dispatchScripts,
        callable $dispatchWebhooks,
        callable $afterAuthenticate
    ) {
        // Run the action
        $validated = $request->validated();
        $result = $authShop($validated['shop'], $validated['code']);

        if ($result->completed) {
            // All good, handle the redirect
            return $this->authenticateSuccess(
                $dispatchScripts,
                $dispatchWebhooks,
                $afterAuthenticate
            );
        }

        // No code, redirect to auth URL
        return $this->authenticateFail(
            $result->url,
            new ShopDomain($validated['shop'])
        );
    }

    /**
     * Handles when authentication is successful.
     *
     * @param callable $dispatchScripts   The action for dispatching scripttag installation.
     * @param callable $dispatchWebhooks  The action for dispatching webhook installation.
     * @param callable $afterAuthenticate The action for dispatching custom actions after authentication.
     *
     * @return RedirectResponse
     */
    protected function authenticateSuccess(
        callable $dispatchScripts,
        callable $dispatchWebhooks,
        callable $afterAuthenticate
    ): RedirectResponse {
        // Fire the post processing jobs
        $dispatchScripts();
        $dispatchWebhooks();
        $afterAuthenticate();

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
     * Handles when authentication is unsuccessful
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
                'shopDomain' => $shopDomain,
            ]
        );
    }
}
