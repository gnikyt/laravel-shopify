<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Actions\AfterAuthenticateAction;
use OhMyBrew\ShopifyApp\Requests\AuthShop;
use OhMyBrew\ShopifyApp\Actions\AuthenticateShopAction;
use OhMyBrew\ShopifyApp\Actions\DispatchScriptsAction;
use OhMyBrew\ShopifyApp\Actions\DispatchWebhooksAction;

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
     * @param AuthShop                $request                 The incoming request.
     * @param AuthenticateShopAction  $authShopAction          The action for authenticating a shop.
     * @param DispatchScriptsAction   $dispatchScriptsAction   The action for dispatching scripttag installation.
     * @param DispatchWebhooksAction  $dispatchWebhooksAction  The action for dispatching webhook installation.
     * @param AfterAuthenticateAction $afterAuthenticateAction The action for dispatching custom actions after authentication.
     *
     * @return ViewView|RedirectResponse
     */
    public function authenticate(
        AuthShop $request,
        AuthenticateShopAction $authShopAction,
        DispatchScriptsAction $dispatchScriptsAction,
        DispatchWebhooksAction $dispatchWebhooksAction,
        AfterAuthenticateAction $afterAuthenticateAction
    ) {
        // Run the action
        $validated = $request->validated();
        $result = $authShopAction($validated['shop'], $validated['code']);

        if ($result->completed) {
            // All good, handle the redirect
            return $this->authenticateSuccess(
                $dispatchScriptsAction,
                $dispatchWebhooksAction,
                $afterAuthenticateAction
            );
        }

        // No code, redirect to auth URL
        return $this->authenticateFail(
            $result->url,
            $validated['shop']
        );
    }

    /**
     * Handles when authentication is successful.
     *
     * @param DispatchScriptsAction   $dispatchScriptsAction   The action for dispatching scripttag installation.
     * @param DispatchWebhooksAction  $dispatchWebhooksAction  The action for dispatching webhook installation.
     * @param AfterAuthenticateAction $afterAuthenticateAction The action for dispatching custom actions after authentication.
     *
     * @return RedirectResponse
     */
    protected function authenticateSuccess(
        DispatchScriptsAction $dispatchScriptsAction,
        DispatchWebhooksAction $dispatchWebhooksAction,
        AfterAuthenticateAction $afterAuthenticateAction
    ): RedirectResponse {
        // Fire the post processing jobs
        $dispatchScriptsAction();
        $dispatchWebhooksAction();
        $afterAuthenticateAction();

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
     * @param string $authUrl    The auth URl to redirect the user to get the code.
     * @param string $shopDomain The shop's domain.
     *
     * @return ViewView
     */
    protected function authenticateFail(string $authUrl, string $shopDomain): ViewView
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
