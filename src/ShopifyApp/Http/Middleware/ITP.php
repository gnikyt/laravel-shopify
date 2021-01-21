<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
        $isItpPath = Str::contains($request->path(), 'itp');
        $itpCookie = $request->cookie('itp');
        $needItpCookie = ! $itpCookie && ! $isItpPath;
        $needItpPermission = $request->query('itp', false);

        if ($needItpCookie && $needItpPermission) {
            // ITP cookie was attempted to be set but it failed
            return $this->ask();
        }

        if ($needItpCookie) {
            // Attempt to set ITP cookie
            return $this->redirect($request);
        }

        return $next($request);
    }

    /**
     * Do a full-page redirect to set attempt to set the ITP cookie.
     *
     * @param Request $request The request object.
     *
     * @return HttpResponse
     */
    protected function redirect(Request $request): HttpResponse
    {
        return Response::make(
            View::make(
                'shopify-app::auth.fullpage_redirect',
                [
                    'authUrl'    => URL::secure('itp').'?'.Arr::query(['shop' => $request->get('shop')]),
                    'shopDomain' => $request->get('shop'),
                ]
            )
        );
    }

    /**
     * Redirect to the ask permission page.
     *
     * @return RedirectResponse
     */
    protected function ask(): RedirectResponse
    {
        return Redirect::route('itp.ask');
    }
}
