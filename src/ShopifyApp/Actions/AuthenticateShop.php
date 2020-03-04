<?php

namespace Osiset\ShopifyApp\Actions;

use Illuminate\Http\Request;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Actions\AfterAuthorize;
use Osiset\ShopifyApp\Actions\DispatchScripts;
use Osiset\ShopifyApp\Actions\DispatchWebhooks;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;

/**
 * Authenticates a shop and fires post authentication actions.
 */
class AuthenticateShop
{
    /**
     * The shop session handler.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The action for authorizing a shop.
     *
     * @var AuthorizeShop
     */
    protected $authorizeShopAction;

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
     * @param ShopSession      $shopSession            The shop session handler.
     * @param IApiHelper       $apiHelper              The API helper.
     * @param AuthorizeShop    $authorizeShopAction    The action for authorizing a shop.
     * @param DispatchScripts  $dispatchScriptsAction  The action for dispatching scripts.
     * @param DispatchWebhooks $dispatchWebhooksAction The action for dispatching webhooks.
     * @param AfterAuthorize   $afterAuthorizeAction   The action for after authorize actions.
     *
     * @return self
     */
    public function __construct(
        ShopSession $shopSession,
        IApiHelper $apiHelper,
        AuthorizeShop $authorizeShopAction,
        DispatchScripts $dispatchScriptsAction,
        DispatchWebhooks $dispatchWebhooksAction,
        AfterAuthorize $afterAuthorizeAction
    ) {
        $this->shopSession = $shopSession;
        $this->apiHelper = $apiHelper;
        $this->authorizeShopAction = $authorizeShopAction;
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
        // Setup
        $shopDomain = new ShopDomain($request->get('shop'));
        $code = $request->get('code');

        // Run the check
        $result = call_user_func($this->authorizeShopAction, $shopDomain, $code);
        if (!$result->completed) {
            // No code, redirect to auth URL
            return [$result, false];
        }

        // Determine if the HMAC is correct
        $this->apiHelper->make();
        if (!$this->apiHelper->verifyRequest($request->all())) {
            // Go to login, something is wrong
            return [$result, null];
        }

        // Fire the post processing jobs
        $shopId = $this->shopSession->getShop()->getId();
        call_user_func($this->dispatchScriptsAction, $shopId, false);
        call_user_func($this->dispatchWebhooksAction, $shopId, false);
        call_user_func($this->afterAuthorizeAction, $shopId);

        return [$result, true];
    }
}
