<?php

namespace Osiset\ShopifyApp\Test\Console;

use ReflectionMethod;
use Osiset\ShopifyApp\Test\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Osiset\ShopifyApp\Console\WebhookJobMakeCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

class WebhookJobMakeCommandTest extends TestCase
{
    public function testItShouldRun(): void
    {
        $this
            ->artisan(
                'shopify-app:make:webhook',
                [
                    'name'  => 'OrdersCreate',
                    'topic' => 'orders/create',
                ]
            )
            ->expectsOutput('For non-GDPR webhooks, don\'t forget to register the webhook in config/shopify-app.php. Example:')
            ->assertExitCode(0);
    }

    public function testShouldMakeUrlFromName(): void
    {
        $application = new ConsoleApplication();
        $testedCommand = $this->app->make(WebhookJobMakeCommand::class);
        $testedCommand->setLaravel($this->app);
        $application->add($testedCommand);

        $command = $application->find('shopify-app:make:webhook');

        $method = new ReflectionMethod($command, 'getUrlFromName');
        $method->setAccessible(true);

        $jobs = [
            'OrdersCreateJob'       => 'orders-create',
            'OrdersCreate'          => 'orders-create',
            'OrdersCreateCustomJob' => 'orders-create-custom',
        ];
        foreach ($jobs as $className => $route) {
            $result = $method->invoke($command, $className);
            $this->assertEquals($result, $route);
        }
    }
}