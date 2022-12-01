<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Objects\Values\WebhookId;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\WebhookJob;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;
use stdClass;

/**
 * Webhook job responsible for handling when the app is uninstalled.
 */
class AppUninstalledJob implements WebhookJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The shop domain.
     *
     * @var ShopDomain|string
     */
    protected $domain;
    /**
     * The webhook data.
     *
     * @var object
     */
    protected $data;
    /**
     * The webhook id
     *
     * @var WebhookId|string
     */
    protected $webhookId;

    /**
     * Create a new job instance.
     *
     * @param ShopDomain|string $shopId    The shop Domain.
     * @param WebhookId|string  $webhookId The webhooks ID.
     * @param stdClass          $data      The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct(string $domain, string $webhookId, stdClass $data)
    {
        $this->domain = $domain;
        $this->data = $data;
        $this->webhookId = $webhookId;
    }

    /**
     * Execute the job.
     *
     * @param IShopCommand      $shopCommand             The commands for shops.
     * @param IShopQuery        $shopQuery               The querier for shops.
     * @param CancelCurrentPlan $cancelCurrentPlanAction The action for cancelling the current plan.
     *
     * @return bool
     */
    public function handle(
        IShopCommand      $shopCommand,
        IShopQuery        $shopQuery,
        CancelCurrentPlan $cancelCurrentPlanAction
    ): bool {
        // Convert the domain
        $this->domain = ShopDomain::fromNative($this->domain);

        // Get the shop
        $shop = $shopQuery->getByDomain($this->domain);
        $shopId = $shop->getId();

        // Cancel the current plan
        $cancelCurrentPlanAction($shopId);

        // Purge shop of token, plan, etc.
        $shopCommand->clean($shopId);

        // Check freemium mode
        $freemium = Util::getShopifyConfig('billing_freemium_enabled');
        if ($freemium === true) {
            // Add the freemium flag to the shop
            $shopCommand->setAsFreemium($shopId);
        }

        // Soft delete the shop.
        $shopCommand->softDelete($shopId);

        return true;
    }
}
