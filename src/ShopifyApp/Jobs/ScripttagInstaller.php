<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use OhMyBrew\ShopifyApp\Services\APIHandler;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

/**
 * Webhook job responsible for handling installing scripttag.
 */
class ScripttagInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop object.
     *
     * @var IShopModel
     */
    protected $shop;

    /**
     * The API helper.
     *
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * Scripttag list.
     *
     * @var array
     */
    protected $scripttags;

    /**
     * Create a new job instance.
     *
     * @param IShopModel  $shop       The shop object.
     * @param array       $scripttags The scripttag list.
     * @param IAPIHelper  $apiHelper  The API helper.
     *
     * @return self
     */
    public function __construct(IShopModel $shop, IApiHelper $apiHelper, array $scripttags)
    {
        $this->shop = $shop;
        $this->apiHelper = $apiHelper;
        $this->scripttags = $scripttags;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        // Get the current scripttags installed on the shop
        $api = $this->apiHelper->setInstance($this->shop->api());
        $shopScripttags = $api->getScriptTags();

        // Keep track of whats created
        $created = [];
        foreach ($this->scripttags as $scripttag) {
            // Check if the required scripttag exists on the shop
            if (!$this->scripttagExists($shopScripttags, $scripttag)) {
                // It does not... create the scripttag
                $api->createScriptTag($scripttag);
                $created[] = $scripttag;
            }
        }

        return $created;
    }

    /**
     * Check if scripttag is in the list.
     *
     * @param array $shopScripttags The scripttags installed on the shop
     * @param array $scripttag      The scripttag
     *
     * @return bool
     */
    protected function scripttagExists(array $shopScripttags, array $scripttag): bool
    {
        foreach ($shopScripttags as $shopScripttag) {
            if ($shopScripttag->src === $scripttag['src']) {
                // Found the scripttag in our list
                return true;
            }
        }

        return false;
    }
}
