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
use Osiset\ShopifyApp\Exceptions\ChargeNotRecurringException;
use Osiset\ShopifyApp\Exceptions\MissingShopDomainException;
use Osiset\ShopifyApp\Http\Requests\StoreUsageCharge;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\NullablePlanId;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;
use Osiset\ShopifyApp\Objects\Values\PlanId;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Storage\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for billing a shop for plans and usage charges.
 */
trait BillingController
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @param Request $request The request object.
     * @param ShopQuery $shopQuery The shop querier.
     * @param GetPlanUrl $getPlanUrl The action for getting the plan URL.
     * @param int|null $plan The plan's ID, if provided in route.
     *
     * @return ViewView
     */
    public function index(
        Request    $request,
        ShopQuery  $shopQuery,
        GetPlanUrl $getPlanUrl,
        ?int       $plan = null
    ): ViewView {
        // Get the shop
        $shop = $shopQuery->getByDomain(ShopDomain::fromNative($request->get('shop')));

        // Get the plan URL for redirect
        $url = $getPlanUrl(
            $shop->getId(),
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
     * @param int $plan The plan's ID.
     * @param Request $request The HTTP request object.
     * @param ShopQuery $shopQuery The shop querier.
     * @param ActivatePlan $activatePlan The action for activating the plan for a shop.
     *
     * @return RedirectResponse
     */
    public function process(
        int          $plan,
        Request      $request,
        ShopQuery    $shopQuery,
        ActivatePlan $activatePlan
    ): RedirectResponse {
        // Get the shop
        $shop = $shopQuery->getByDomain(ShopDomain::fromNative($request->query('shop')));
        if (!$request->has('charge_id')) {
            return Redirect::route(Util::getShopifyConfig('route_names.home'), [
                'shop' => $shop->getDomain()->toNative(),
                'host' => base64_encode($shop->getDomain()->toNative().'/admin'),
            ]);
        }
        // Activate the plan and save
        $result = $activatePlan(
            $shop->getId(),
            PlanId::fromNative($plan),
            ChargeReference::fromNative((int) $request->query('charge_id'))
        );

        // Go to homepage of app
        return Redirect::route(Util::getShopifyConfig('route_names.home'), array_merge([
            'shop' => $shop->getDomain()->toNative(),
        ], Util::useNativeAppBridge() ? [] : [
            'host' => base64_encode($shop->getDomain()->toNative().'/admin'),
            'billing' => $result ? 'success' : 'failure',
        ]))->with(
            $result ? 'success' : 'failure',
            'billing'
        );
    }

    /**
     * Allows for setting a usage charge.
     *
     * @param StoreUsageCharge $request The verified request.
     * @param ActivateUsageCharge $activateUsageCharge The action for activating a usage charge.
     * @param ShopQuery $shopQuery The shop querier.
     *
     * @throws MissingShopDomainException|ChargeNotRecurringException
     *
     * @return RedirectResponse
     */
    public function usageCharge(
        StoreUsageCharge    $request,
        ActivateUsageCharge $activateUsageCharge,
        ShopQuery           $shopQuery
    ): RedirectResponse {
        $shopDomain = NullableShopDomain::fromNative($request->get('shop'));
        // Get the shop from the shop param after it has been validated.
        if ($shopDomain->isNull()) {
            throw new MissingShopDomainException('Shop parameter is missing from request');
        }
        $shop = $shopQuery->getByDomain($shopDomain);

        // Valid the request params.
        $validated = $request->validated();

        // Create the transfer object
        $ucd = new UsageChargeDetailsTransfer();
        $ucd->price = $validated['price'];
        $ucd->description = $validated['description'];

        // Activate and save the usage charge
        $activateUsageCharge($shop->getId(), $ucd);

        // All done, return with success
        return isset($validated['redirect'])
            ? Redirect::to($validated['redirect'])->with('success', 'usage_charge')
            : Redirect::back()->with('success', 'usage_charge');
    }
}
