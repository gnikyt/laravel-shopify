<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook job responsible for handling installing scripttag.
 */
class ScripttagInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop object.
     *
     * @var object
     */
    protected $shop;

    /**
     * Scripttag list.
     *
     * @var array
     */
    protected $scripttags;

    /**
     * Create a new job instance.
     *
     * @param object $shop       The shop object
     * @param array  $scripttags The scripttag list
     *
     * @return void
     */
    public function __construct($shop, array $scripttags)
    {
        $this->shop = $shop;
        $this->scripttags = $scripttags;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        // Keep track of whats created
        $created = [];

        // Get the current scripttags installed on the shop
        $api = $this->shop->api();
        $shopScripttags = $api->rest(
            'GET',
            '/admin/script_tags.json',
            [
                'limit'  => 250,
                'fields' => 'id,src',
            ]
        )->body->script_tags;

        foreach ($this->scripttags as $scripttag) {
            // Check if the required scripttag exists on the shop
            if (!$this->scripttagExists($shopScripttags, $scripttag)) {
                // It does not... create the scripttag
                $api->rest('POST', '/admin/script_tags.json', ['script_tag' => $scripttag]);
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
    protected function scripttagExists(array $shopScripttags, array $scripttag)
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
