<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use stdClass;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Osiset\ShopifyApp\Objects\Values\ShopId;
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
     * The shop ID.
     *
     * @var ShopId
     */
    protected $shopId;

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
     * @param ShopId       $shopId                  The shop ID.
     * @param stdClass     $data                    The webhook data (JSON decoded).
     * @param callable     $cancelCurrentPlanAction Action for cancelling current plan.
     *
     * @return self
     */
    public function __construct(
        ShopId $shopId,
        stdClass $data,
        callable $cancelCurrentPlanAction
    ) {
        $this->shopId = $shopId;
        $this->data = $data;
        $this->cancelCurrentPlanAction = $cancelCurrentPlanAction;
    }

    /**
     * Execute the job.
     *
     * @param IShopCommand $shopCommand The commands for shops.
     *
     * @return bool
     */
    public function handle(IShopCommand $shopCommand): bool
    {
        // Cancel the current plan
        call_user_func($this->cancelCurrentPlanAction, $this->shopId);
        
        // Purge shop of token, plan, etc.
        $shopCommand->clean($this->shopId);

        // Soft delete the shop.
        $shopCommand->softDelete($this->shopId);

        return true;
    }
}
