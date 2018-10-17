<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's instance.
     *
     * @var string
     */
    protected $shop;

    /**
     * Shop's myshopify domain.
     *
     * @var string
     */
    protected $shopDomain;

    /**
     * The webhook data.
     *
     * @var object
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param string $shopDomain The shop's myshopify domain
     * @param object $data       The webhook data (JSON decoded)
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->data = $data;
        $this->shopDomain = $shopDomain;
        $this->shop = $this->findShop();
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        if (!$this->shop) {
            return false;
        }

        $this->cleanShop();
        $this->softDeleteShop();
        $this->cancelCharge();

        return true;
    }

    /**
     * Clean the shop data on uninstall.
     *
     * @return void
     */
    protected function cleanShop()
    {
        $this->shop->shopify_token = null;
        $this->shop->save();
    }

    /**
     * Soft deletes the shop in the database.
     *
     * @return void
     */
    protected function softDeleteShop()
    {
        $this->shop->delete();
        $this->shop->charges()->delete();
    }

    /**
     * Cancels a recurring or one-time charge.
     *
     * @return void
     */
    protected function cancelCharge()
    {
        $lastCharge = $this->shop->charges()
            ->withTrashed()
            ->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastCharge && !$lastCharge->isDeclined() && !$lastCharge->isCancelled()) {
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
        $shopModel = config('shopify-app.shop_model');
        return $shopModel::where(['shopify_domain' => $this->shopDomain])->first();
    }
}
