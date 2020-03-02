<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Requests\StoreUsageCharge;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingController
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param int      $planId     The plan's ID.
     * @param callable $getPlanUrl The action for getting the plan URL.
     *
     * @return ViewView
     */
    public function index(int $planId, callable $getPlanUrl): ViewView
    {
        // Do a fullpage redirect
        return View::make(
            'shopify-app::billing.fullpage_redirect',
            [
                'url' => $getPlanUrl(new PlanId($planId)),
            ]
        );
    }

    /**
     * Processes the response from the customer.
     *
     * @param Request  $request      The HTTP request object.
     * @param int      $planId       The plan's ID.
     * @param callable $activatePlan The action for activating the plan for a shop.
     *
     * @return RedirectResponse
     */
    public function process(
        Request $request,
        int $planId,
        callable $activatePlanAction
    ): RedirectResponse {
        // Activate the plan and save
        $result = $activatePlanAction(
            new ShopDomain(ShopifyApp::shop()->name),
            new PlanId($planId),
            new ChargeReference($request->query('charge_id'))
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
     * @param StoreUsageCharge $request                   The verified request.
     * @param callable         $activateUsageChargeAction The action for activating a usage charge.
     *
     * @return RedirectResponse
     */
    public function usageCharge(
        StoreUsageCharge $request,
        callable $activateUsageChargeAction
    ): RedirectResponse {
        $validated = $request->validated();

        // Activate and save the usage charge
        $activateUsageChargeAction(
            new ShopDomain(ShopifyApp::shop()->name),
            $validated['price'],
            $validated['description']
        );

        // All done, return with success
        return isset($validated['redirect']) ?
            Redirect::to($validated['redirect'])->with('success', 'usage_charge') :
            Redirect::back()->with('success', 'usage_charge');
    }
}
