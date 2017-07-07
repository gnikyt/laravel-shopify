<?php namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;

class AuthShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (\ShopifyApp::shop() === false) {
            // Shall not pass
            abort(403);
        }

        // Move on, authenticated
        return $next($request);
    }
}
