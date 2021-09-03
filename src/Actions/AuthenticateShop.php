<?php

namespace Osiset\ShopifyApp\Actions;

use Illuminate\Http\Request;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Authenticates a shop and fires post authentication actions.
 */
class AuthenticateShop
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The action for installing a shop.
     *
     * @var InstallShop
     */
    protected $installShopAction;

    /**
     * The action for dispatching scripts.
     *
     * @var DispatchScripts
     */
    protected $dispatchScriptsAction;

    /**
     * The action for dispatching webhooks.
     *
     * @var DispatchWebhooks
     */
    protected $dispatchWebhooksAction;

    /**
     * The action for after authorize actions.
     *
     * @var AfterAuthorize
     */
    protected $afterAuthorizeAction;

    /**
     * Setup.
     *
     * @param IApiHelper       $apiHelper              The API helper.
     * @param InstallShop      $installShopAction      The action for installing a shop.
     * @param DispatchScripts  $dispatchScriptsAction  The action for dispatching scripts.
     * @param DispatchWebhooks $dispatchWebhooksAction The action for dispatching webhooks.
     * @param AfterAuthorize   $afterAuthorizeAction   The action for after authorize actions.
     *
     * @return void
     */
    public function __construct(
        IApiHelper $apiHelper,
        InstallShop $installShopAction,
        DispatchScripts $dispatchScriptsAction,
        DispatchWebhooks $dispatchWebhooksAction,
        AfterAuthorize $afterAuthorizeAction
    ) {
        $this->apiHelper = $apiHelper;
        $this->installShopAction = $installShopAction;
        $this->dispatchScriptsAction = $dispatchScriptsAction;
        $this->dispatchWebhooksAction = $dispatchWebhooksAction;
        $this->afterAuthorizeAction = $afterAuthorizeAction;
    }

    /**
     * Execution.
     *
     * @param Request $request The request object.
     *
     * @return array
     */
    public function __invoke(Request $request): array
    {
        // Run the check
        /** @var $result array */
        $result = call_user_func(
            $this->installShopAction,
            ShopDomain::fromNative($request->get('shop')),
            $request->query('code')
        );

        if (! $result['completed']) {
            // No code, redirect to auth URL
            return [$result, false];
        }

        // Determine if the HMAC is correct
        $this->apiHelper->make();
        if (! $this->apiHelper->verifyRequest($request->all())) {
            // Throw exception, something is wrong
            return [$result, null];
        }

        // Fire the post processing jobs
        call_user_func($this->dispatchScriptsAction, $result['shop_id'], false);
        call_user_func($this->dispatchWebhooksAction, $result['shop_id'], false);
        call_user_func($this->afterAuthorizeAction, $result['shop_id']);

        return [$result, true];
    }
}
