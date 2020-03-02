<?php

namespace Osiset\ShopifyApp\Messaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopId;

/**
 * Webhook job responsible for handling installing scripttag.
 */
class ScripttagInstaller implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The shop's ID.
     *
     * @var ShopId
     */
    protected $shopId;

    /**
     * Action for creating scripttags.
     *
     * @var string
     */
    protected $createScriptsAction;

    /**
     * The scripts to add.
     *
     * @var array
     */
    protected $configScripts;

    /**
     * Create a new job instance.
     *
     * @param ShopId $shopId              The shop ID.
     * @param string $createScriptsAction Action for creating scripttags.
     * @param array  $configScripts       The scripts to add.
     *
     * @return self
     */
    public function __construct(ShopId $shopId, callable $createScriptsAction, array $configScripts)
    {
        $this->shopId = $shopId;
        $this->createScriptsAction = $createScriptsAction;
        $this->configScripts = $configScripts;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        return call_user_func(
            $this->createScriptsAction,
            $this->shopId,
            $this->configScripts
        );
    }
}
