<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Webhook job responsible for handling installing scripttag.
 */
class ScripttagInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop's ID.
     *
     * @var int
     */
    protected $shopId;

    /**
     * Action for creating scripttags.
     *
     * @var callable
     */
    protected $createScriptsAction;

    /**
     * Create a new job instance.
     *
     * @param int        $shopId              The shop ID.
     * @param callable   $createScriptsAction Action for creating scripttags.
     *
     * @return self
     */
    public function __construct(
        int $shopId,
        callable $createScriptsAction
    ) {
        $this->shopId = $shopId;
        $this->createScriptsAction = $createScriptsAction;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        return call_user_func($this->createScriptsAction, $this->shopId);
    }
}
