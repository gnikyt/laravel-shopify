<?php

namespace OhMyBrew\ShopifyApp\Test\Console;

use OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;

class WebhookJobMakeCommandTest extends TestCase
{
    public function testItShouldRun()
    {
        $application = new ConsoleApplication();

        $testedCommand = $this->app->make(WebhookJobMakeCommand::class);
        $testedCommand->setLaravel($this->app);
        $application->add($testedCommand);

        $command = $application->find('shopify-app:make:webhook');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'name'    => 'OrdersCreateJob',
            'topic'   => 'orders/create',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains("Don't forget to register the webhook in config/shopify-app.php", $output);
        $this->assertContains("'address' => 'https://your-domain.com/webhook/orders-create'", $output);
        $this->assertContains("'topic' => 'orders/create',", $output);
    }

    public function testShouldMakeUrlFromName()
    {
        $application = new ConsoleApplication();
        $testedCommand = $this->app->make(WebhookJobMakeCommand::class);
        $testedCommand->setLaravel($this->app);
        $application->add($testedCommand);

        $command = $application->find('shopify-app:make:webhook');

        $method = new ReflectionMethod($command, 'getUrlFromName');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'OrdersCreateJob');
        $result2 = $method->invoke($command, 'OrdersCreate');
        $result3 = $method->invoke($command, 'OrdersCreateCustomJob');

        $this->assertEquals($result, 'orders-create');
        $this->assertEquals($result2, 'orders-create');
        $this->assertEquals($result3, 'orders-create-custom');
    }

    public function testShouldReturnStub()
    {
        $application = new ConsoleApplication();
        $testedCommand = $this->app->make(WebhookJobMakeCommand::class);
        $testedCommand->setLaravel($this->app);
        $application->add($testedCommand);

        $command = $application->find('shopify-app:make:webhook');

        $method = new ReflectionMethod($command, 'getStub');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertContains('/stubs/webhook-job.stub', $result);
    }
}
