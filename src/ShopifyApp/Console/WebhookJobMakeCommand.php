<?php

namespace Osiset\ShopifyApp\Console;

use Illuminate\Foundation\Console\JobMakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class WebhookJobMakeCommand extends JobMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'shopify-app:make:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new webhook job class';

    /**
     * Get the stub file for the generator.
     *
     * @codeCoverageIgnore No point testing something that only returns a string.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__.'/stubs/webhook-job.stub';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
            ['topic', InputArgument::REQUIRED, 'The event/topic for the job (orders/create, products/update, etc)'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $result = parent::handle();

        // Remind user to enter job into config
        $this->info("For non-GDPR webhooks, don't forget to register the webhook in config/shopify-app.php. Example:");
        $this->info("
    'webhooks' => [
        [
            'topic' => '{$this->argument('topic')}',
            'address' => 'https://your-domain.com/webhook/{$this->getUrlFromName(trim($this->argument('name')))}'
        ]
    ]
        ");

        return $result;
    }

    /**
     * Append "Job" to the end of class name.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        $name = parent::getNameInput();
        $suffix = 'Job';
        if (! Str::endsWith($name, $suffix)) {
            $name .= $suffix;
        }

        return $name;
    }

    /**
     * Converts the job class name into a URL endpoint.
     *
     * @param string $name The name of the job
     *
     * @return string
     */
    protected function getUrlFromName(string $name): string
    {
        if (Str::endsWith($name, 'Job')) {
            $name = substr($name, 0, -3);
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}
