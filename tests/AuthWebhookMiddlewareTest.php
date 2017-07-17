<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use Illuminate\Support\Facades\Input;

class AuthWebhookMiddlewareTest extends TestCase
{
    public function testRuns()
    {
        $called = false;
        $result = (new AuthWebhook)->handle(request(), function($request) use(&$called) {
            $called = true;
        });

        $this->assertEquals($called);
    }
}
