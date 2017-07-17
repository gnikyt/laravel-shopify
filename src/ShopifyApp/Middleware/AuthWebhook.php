<?php namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthWebhook
{
    /**
     * Handle an incoming request to ensure webhook is valid
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
      // ... validate HMAC and headers
    }
}
