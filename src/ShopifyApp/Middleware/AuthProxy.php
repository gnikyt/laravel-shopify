<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthProxy
{
    /**
     * Handle an incoming request to ensure it is valid.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the data we need
        $query = request()->all();
        $signature = $query['signature'];

        // Remove signature since its not part of the signature calculation, sort it
        unset($query['signature']);
        ksort($query);

        // Build a query string without query characters
        $queryCompiled = [];
        foreach ($query as $key => $value) {
            $queryCompiled[] = "{$key}=".(is_array($value) ? implode($value, ',') : $value);
        }
        $queryJoined = implode($queryCompiled, '');

        // Build a local signature
        $signatureLocal = hash_hmac('sha256', $queryJoined, config('shopify-app.api_secret'));
        if ($signature !== $signatureLocal || !isset($query['shop'])) {
            // Issue with HMAC or missing shop header
            abort(401, 'Invalid proxy signature');
        }

        // All good, process proxy request
        return $next($request);
    }
}
