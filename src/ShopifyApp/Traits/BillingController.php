<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Osiset\ShopifyApp\Actions\ActivatePlan;
use Osiset\ShopifyApp\Actions\ActivateUsageCharge;
use Osiset\ShopifyApp\Actions\GetPlanUrl;
use Osiset\ShopifyApp\Http\Requests\StoreUsageCharge;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\NullablePlanId;
use Osiset\ShopifyApp\Objects\Values\PlanId;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingController
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param int|null    $plan        The plan's ID, if provided in route.
     * @param GetPlanUrl  $getPlanUrl  The action for getting the plan URL.
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return ViewView
     */
    public function index(?int $plan = null, GetPlanUrl $getPlanUrl, ShopSession $shopSession): ViewView
    {
        // Get the plan URL for redirect
        $url = $getPlanUrl(
            $shopSession->getShop()->getId(),
            NullablePlanId::fromNative($plan)
        );

        // Do a fullpage redirect
        return View::make(
            'shopify-app::billing.fullpage_redirect',
            ['url' => $url]
        );
    }

    /**
     * Processes the response from the customer.
     *
     * @param int          $plan         The plan's ID.
     * @param Request      $request      The HTTP request object.
     * @param ActivatePlan $activatePlan The action for activating the plan for a shop.
     * @param ShopSession  $shopSession The shop session helper.
     *
     * @return RedirectResponse
     */
    public function process(
        int $plan,
        Request $request,
        ActivatePlan $activatePlan,
        ShopSession $shopSession
    ): RedirectResponse {
        // Activate the plan and save
        $result = $activatePlan(
            $shopSession->getShop()->getId(),
            PlanId::fromNative($plan),
            ChargeReference::fromNative((int) $request->query('charge_id'))
        );

        // Go to homepage of app
        return Redirect::route(Util::getShopifyConfig('route_names.home'))->with(
            $result ? 'success' : 'failure',
            'billing'
        );
    }

    /**
     * Allows for setting a usage charge.
     *
     * @param StoreUsageCharge    $request             The verified request.
     * @param ActivateUsageCharge $activateUsageCharge The action for activating a usage charge.
     * @param ShopSession         $shopSession         The shop session helper.
     *
     * @return RedirectResponse
     */
    public function usageCharge(
        StoreUsageCharge $request,
        ActivateUsageCharge $activateUsageCharge,
        ShopSession $shopSession
    ): RedirectResponse {
        $validated = $request->validated();

        // Create the transfer object
        $ucd = new UsageChargeDetailsTransfer();
        $ucd->price = $validated['price'];
        $ucd->description = $validated['description'];

        // Activate and save the usage charge
        $activateUsageCharge(
            $shopSession->getShop()->getId(),
            $ucd
        );

        // All done, return with success
        return isset($validated['redirect']) ?
            Redirect::to($validated['redirect'])->with('success', 'usage_charge') :
            Redirect::back()->with('success', 'usage_charge');
    }
}
