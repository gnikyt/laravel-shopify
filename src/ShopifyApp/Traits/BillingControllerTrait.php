<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Actions\GetPlanUrl;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\UsageCharge;
use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use OhMyBrew\ShopifyApp\Requests\StoreUsageCharge;
use OhMyBrew\ShopifyApp\Actions\ActivatePlan;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param int        $planId     The plan's ID.
     * @param GetPlanUrl $getPlanUrl The action for getting the plan URL.
     *
     * @return ViewView
     */
    public function index(int $planId, GetPlanUrl $getPlanUrl): ViewView
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
     * @param Request      $request      The HTTP request object.
     * @param int          $planId       The plan's ID.
     * @param ActivatePlan $activatePlan The action for activating the plan for a shop.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(
        Request $request,
        int $planId,
        ActivatePlan $activatePlanForShop
    ): RedirectResponse {
        // Activate the plan and save
        $result = $activatePlanForShop(
            ShopifyApp::shop(),
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
     * @param \OhMyBrew\ShopifyApp\Requests\StoreUsageCharge $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function usageCharge(StoreUsageCharge $request)
    {
        // Activate and save the usage charge
        $validated = $request->validated();
        $uc = new UsageCharge(ShopifyApp::shop(), $validated);
        $uc->activate();
        $uc->save();

        // All done, return with success
        return isset($validated['redirect']) ?
            Redirect::to($validated['redirect'])->with('success', 'usage_charge') :
            Redirect::back()->with('success', 'usage_charge');
    }
}
