<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

class AuthToken
{
    use ConfigAccessible;

    /**
     * The shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Constructor.
     *
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return void
     */
    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
    }

    /**
     * Handle an incoming request.
     *
     * Get the bearer token, validate and verify, and create a
     * session based on the contents.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return Response::make('Missing authentication token', 401);
        }

        // It's "url safe" base64, so `+` is `-` and `/` is `_`
        // The header is fixed so include it here
        if (!preg_match('/^eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_\=]*$/', $token)) {
            return Response::make('Malformed token', 400);
        }

        if (!$this->checkSignature($token)) {
            return Response::make('Unable to verify signature', 400);
        }

        $parts = explode('.', $token);

        $body = $this->base64url_decode($parts[1]);
        $signature = $parts[2];

        if (!$body || !$signature) {
            return Response::make('Malformed token', 400);
        }

        $body = json_decode($body);

        if (!$body ||
            !isset($body->iss) ||
            !isset($body->dest) ||
            !isset($body->aud) ||
            !isset($body->sub) ||
            !isset($body->exp) ||
            !isset($body->nbf) ||
            !isset($body->iat) ||
            !isset($body->jti) ||
            !isset($body->sid)) {
            return Response::make('Malformed token', 400);
        }

        $now = time();

        if (($now >= $body->exp) || ($now <= $body->nbf) || ($now <= $body->iat)) {
            return Response::make('Expired token', 403);
        }

        if (!stristr($body->iss, $body->dest)) {
            return Response::make('Invalid token', 400);
        }

        if ($body->aud !== $this->getConfig('api_key')) {
            return Response::make('Invalid token', 400);
        }

        // All is well, login
        $url = parse_url($body->dest);

        $this->shopSession->make(ShopDomain::fromNative($url['host']));
        $this->shopSession->setSessionToken($body->sid);

        return $next($request);
    }

    private function checkSignature($token)
    {
        $parts = explode('.', $token);
        $signature = array_pop($parts);
        $check = implode('.', $parts);

        $secret = $this->getConfig('api_secret');
        $hmac = hash_hmac('sha256', $check, $secret, true);
        $encoded = $this->base64url_encode($hmac);

        return $encoded === $signature;
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
