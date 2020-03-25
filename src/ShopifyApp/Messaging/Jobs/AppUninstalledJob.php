<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use stdClass;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;

/**
 * Webhook job responsible for handling when the app is uninstalled.
 */
class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The shop domain.
     *
     * @var ShopDomain
     */
    protected $domain;

    /**
     * The webhook data.
     *
     * @var object
     */
    protected $data;

    /**
     * Action for cancelling current plan.
     *
     * @var callable
     */
    protected $cancelCurrentPlanAction;

    /**
     * Create a new job instance.
     *
     * @param ShopDomain   $domain                  The shop domain.
     * @param stdClass     $data                    The webhook data (JSON decoded).
     * @param callable     $cancelCurrentPlanAction Action for cancelling current plan.
     *
     * @return self
     */
    public function __construct(
        ShopDomain $domain,
        stdClass $data,
        callable $cancelCurrentPlanAction
    ) {
        $this->domain = $domain;
        $this->data = $data;
        $this->cancelCurrentPlanAction = $cancelCurrentPlanAction;
    }

    /**
     * Execute the job.
     *
     * @param IShopCommand $shopCommand The commands for shops.
     * @param IShopQuery   $shopQuery   The querier for shops.
     *
     * @return bool
     */
    public function handle(IShopCommand $shopCommand, IShopQuery $shopQuery): bool
    {
        // Get the shop
        $shop = $shopQuery->getByDomain($this->domain);
        $shopId = $shop->getId();

        // Cancel the current plan
        call_user_func($this->cancelCurrentPlanAction, $shopId);
        
        // Purge shop of token, plan, etc.
        $shopCommand->clean($shopId);

        // Soft delete the shop.
        $shopCommand->softDelete($shopId);

        return true;
    }
}
