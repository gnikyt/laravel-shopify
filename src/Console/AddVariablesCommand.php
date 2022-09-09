<?php

namespace Osiset\ShopifyApp\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddVariablesCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'shopify-app:add:variables';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'shopify-app:add:variables
        {--always-no : Skip generating variable if it already exists.}
        {--force : Skip confirmation when overwriting an existing variable.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add default variables to env';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $env = $this->envPath();

        if (file_exists($env)) {
            foreach ($this->shopifyVariables() as $key => $variable) {
                if (Str::contains(file_get_contents($env), $key) === false) {
                    file_put_contents($env, PHP_EOL."$key=$variable", FILE_APPEND);
                } else {
                    if ($this->option('always-no')) {
                        $this->comment("Variable $key already exists. Skipping...");

                        continue;
                    }

                    if ($this->isConfirmed($key) === false) {
                        $this->comment('There has been no change.');

                        continue;
                    }
                }
            }
        }

        $this->successResult();
    }

    /**
     * Display result.
     *
     * @return void
     */
    protected function successResult(): void
    {
        $this->info('All variables will be set');
    }

    /**
     * Check if the modification is confirmed.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isConfirmed(string $key): bool
    {
        return $this->option('force')
            ? true
            : $this->confirm(
                "This will invalidate {$key} variable. Are you sure you want to override {$key}?"
            );
    }

    /**
     * Get shopify env variables
     *
     * @return array
     */
    public function shopifyVariables(): array
    {
        return [
            'SHOPIFY_APP_NAME' => config('app.name'),
            'SHOPIFY_API_KEY' => '',
            'SHOPIFY_API_SECRET' => '',
            'SHOPIFY_API_SCOPES' => config('shopify-app.api_scopes'),
            'AFTER_AUTHENTICATE_JOB' => "\App\Jobs\AfterAuthenticateJob",
        ];
    }

    /**
     * Get the .env file path.
     *
     * @return string
     */
    protected function envPath()
    {
        $enviromemtFile = $this->laravel->environmentFile();
        $baseEnvFile = $this->laravel->basePath('.env');

        if (file_exists($enviromemtFile) && method_exists($this->laravel, 'environmentFile')) {
            return $enviromemtFile;
        }

        return $baseEnvFile;
    }
}
