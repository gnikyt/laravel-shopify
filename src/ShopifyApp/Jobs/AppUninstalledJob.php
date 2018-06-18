<?php

namespace OhMyBrew\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's instance.
     *
     * @var string
     */
    public $shop;

    /**
     * Shop's myshopify domain.
     *
     * @var string
     */
    public $shopDomain;

    /**
     * The webhook data.
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string $shopDomain The shop's myshopify domain
     * @param object $webhook    The webhook data (JSON decoded)
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
        $this->shop = $this->findShop();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->softDeleteShop();
        $this->cancelCharge();
    }

    /**
     * Soft deletes the shop in the database.
     *
     * @return void
     */
    protected function softDeleteShop()
    {
        if ($this->shop) {
            $this->shop->delete();
            $this->shop->charges()->delete();
        }
    }

    /**
     * Cancels a recurring or one-time charge.
     *
     * @return void
     */
    protected function cancelCharge()
    {
        $lastCharge = $shop->charges()
            ->where(function ($query) {
                $query->latestByType(Charge::CHARGE_RECURRING);
            })->orWhere(function ($query) {
                $query->latestByType(Charge::CHARGE_ONETIME);
            })->latest()->first();

        if ($lastCharge && (!$lastCharge->isDeclined() && !$lastCharge->isCancelled())) {
            $lastCharge->status = 'cancelled';
            $lastCharge->cancelled_on = Carbon::today()->format('Y-m-d');
            $lastCharge->save();
        }
    }

    /**
     * Finds the shop based on domain from the webhook.
     *
     * @return Shop|null
     */
    protected function findShop()
    {
        return Shop::where(['shopify_domain' => $this->shopDomain])->first();
    }
}
