<?php

namespace OhMyBrew\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Webhook job responsible for handling when the app is uninstalled.
 */
class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop ID.
     *
     * @var int
     */
    protected $shopId;

    /**
     * The webhook data.
     *
     * @var object
     */
    protected $data;

    /**
     * Commands for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * Action for cancelling current plan.
     *
     * @var callable
     */
    protected $cancelCurrentPlanAction;

    /**
     * Create a new job instance.
     *
     * @param int          $shopId                  The shop ID.
     * @param object       $data                    The webhook data (JSON decoded).
     * @param IShopCommand $shopCommand             The commands for shops.
     * @param callable     $cancelCurrentPlanAction Action for cancelling current plan.
     *
     * @return self
     */
    public function __construct(
        int $shopId,
        object $data,
        IShopCommand $shopCommand,
        callable $cancelCurrentPlanAction
    ) {
        $this->shopId = $shopId;
        $this->data = $data;
        $this->shopCommand = $shopCommand;
        $this->cancelCurrentPlanAction = $cancelCurrentPlanAction;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $shopId = new ShopId($this->shopId);

        call_user_func($this->cancelCurrentPlanAction, $shopId);
        $this->shopCommand->clean($shopId);
        $this->shopCommand->softDelete($shopId);

        return true;
    }
}
