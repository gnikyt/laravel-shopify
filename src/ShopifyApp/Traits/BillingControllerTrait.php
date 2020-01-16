<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use Illuminate\Contracts\View\View as ViewView;
use OhMyBrew\ShopifyApp\Actions\GetPlanUrlAction;
use OhMyBrew\ShopifyApp\Requests\StoreUsageCharge;
use OhMyBrew\ShopifyApp\Actions\ActivatePlanAction;
use OhMyBrew\ShopifyApp\Actions\ActivateUsageChargeAction;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param int              $planId     The plan's ID.
     * @param GetPlanUrlAction $getPlanUrl The action for getting the plan URL.
     *
     * @return ViewView
     */
    public function index(int $planId, GetPlanUrlAction $getPlanUrl): ViewView
    {
        // Do a fullpage redirect
        return View::make(
            'shopify-app::billing.fullpage_redirect',
            [
                'url' => $getPlanUrl($planId),
            ]
        );
    }

    /**
     * Processes the response from the customer.
     *
     * @param Request            $request      The HTTP request object.
     * @param int                $planId       The plan's ID.
     * @param ActivatePlanAction $activatePlan The action for activating the plan for a shop.
     *
     * @return RedirectResponse
     */
    public function process(
        Request $request,
        int $planId,
        ActivatePlanAction $activatePlanAction
    ): RedirectResponse {
        // Activate the plan and save
        $result = $activatePlanAction(
            ShopifyApp::shop()->shopify_domain,
            $planId,
            $request->query('charge_id')
        );

        // Go to homepage of app
        return Redirect::route('home')->with(
            $result ? 'success' : 'failure',
            'billing'
        );
    }

    /**
     * Allows for setting a usage charge.
     *
     * @param StoreUsageCharge          $request                   The verified request.
     * @param ActivateUsageChargeAction $activateUsageChargeAction The action for activating a usage charge.
     *
     * @return RedirectResponse
     */
    public function usageCharge(
        StoreUsageCharge $request,
        ActivateUsageChargeAction $activateUsageChargeAction
    ): RedirectResponse {
        $validated = $request->validated();

        // Activate and save the usage charge
        $activateUsageChargeAction(
            ShopifyApp::shop()->shopify_domain,
            $validated['price'],
            $validated['description']
        );

        // All done, return with success
        return isset($validated['redirect']) ?
            Redirect::to($validated['redirect'])->with('success', 'usage_charge') :
            Redirect::back()->with('success', 'usage_charge');
    }
}
