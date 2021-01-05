<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

/**
 * Ensuring ITP process.
 */
class ITP
{
    /**
     * Handle an incoming request.
     * Checks and handles ITP.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->cookie('itp') && $request->path() !== 'itp') {
            return Response::make(
                View::make(
                    'shopify-app::auth.fullpage_redirect',
                    [
                        'authUrl'    => URL::secure('itp').'?shop='.$request->get('shop'),
                        'shopDomain' => $request->get('shop'),
                    ]
                )
            );
        }

        return $next($request);
    }
}
