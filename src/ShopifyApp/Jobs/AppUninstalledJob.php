<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;

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
        call_user_func($this->cancelCurrentPlanAction, $this->shopId);
        $this->shopCommand->clean($this->shopId);
        $this->shopCommand->softDelete($this->shopId);

        return true;
    }
}
