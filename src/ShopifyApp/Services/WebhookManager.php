<?php

namespace OhMyBrew\ShopifyApp\Services;

use OhMyBrew\ShopifyApp\Traits\ShopAccessibleTrait;

/**
 * Responsible for managing webhooks.
 */
class WebhookManager
{
    use ShopAccessibleTrait;

    /**
     * The create webhooks action.
     *
     * @var callable
     */
    protected $createWebhooksAction;

    /**
     * The delete webhooks action.
     *
     * @var callable
     */
    protected $deleteWebhooksAction;

    /**
     * Create a new job instance.
     *
     * @param callable $createWebhooksAction The create webhooks action.
     * @param callable $deleteWebhooksAction The delete webhoooks action.
     *
     * @return self
     */
    public function __construct(callable $createWebhooksAction, callable $deleteWebhooksAction)
    {
        $this->createWebhooksAction = $createWebhooksAction;
        $this->deleteWebhooksAction = $deleteWebhooksAction;
    }

    /**
     * Creates webhooks (if they do not exist).
     *
     * @return array
     */
    public function createWebhooks(): array
    {
        return call_user_func(
            $this->createWebhooksAction,
            $this->shop->shopify_domain
        );
    }

    /**
     * Deletes webhooks in the shop tied to the app.
     *
     * @return array
     */
    public function deleteWebhooks(): array
    {
        return call_user_func(
            $this->deleteWebhooksAction,
            $this->shop->shopify_domain
        );
    }

    /**
     * Recreates the webhooks.
     *
     * @return void
     */
    public function recreateWebhooks(): void
    {
        $this->deleteWebhooks();
        $this->createWebhooks();
    }
}
