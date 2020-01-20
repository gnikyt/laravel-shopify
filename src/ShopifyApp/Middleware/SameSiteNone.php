<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Jenssegers\Agent\Agent;

class SameSiteNone
{
    /**
     * Sets SameSite=None while checking for incompatible browsers.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userAgent = $request->userAgent();
        $headers = $request->header();

        // set SameSite none to supported browsers only
        if ($this->isBrowserSameSiteNoneCompatible($userAgent, $headers)) {
            config(['session.secure' => true]);
            config(['session.same_site' => 'none']);
        } // else continue with original session config

        return $next($request);
    }

    /**
     * Checks using the user agent if the browser is compatible with SameSite None.
     * If a null is passed for any of the variables, it will be loaded from the request.
     *
     * @param string|null $userAgent
     * @param array|null  $headers
     *
     * @return bool True if compatible false if not
     */
    public function isBrowserSameSiteNoneCompatible($userAgent, $headers)
    {
        // manually initialise the parser so that this function is testable
        $agent = new Agent();
        $agent->setUserAgent($userAgent);
        $agent->setHttpHeaders($headers);

        // parse the User Agent
        $browser = $agent->browser();
        $browserVersion = $agent->version($browser, Agent::VERSION_TYPE_FLOAT);
        $platform = $agent->platform();
        $platformVersion = $agent->version($platform, Agent::VERSION_TYPE_FLOAT);

        // check for incompatible browsers based on https://www.chromium.org/updates/same-site/incompatible-clients
        $browserIsCompatible = true;
        if ($browser == 'Safari' && $platform == 'iOS' && $platformVersion >= 12 && $platformVersion < 13) {
            $browserIsCompatible = false;
        } elseif ($browser == 'Safari' && $platform == 'OS X' && $platformVersion >= 10.14 && $platformVersion < 10.15) {
            $browserIsCompatible = false;
        } elseif ($browser == 'Chrome' && $browserVersion >= 51 && $browserVersion < 67) {
            $browserIsCompatible = false;
        } elseif ($browser == 'UCBrowser' && $browserVersion < 12.13) {
            $browserIsCompatible = false;
        }

        return $browserIsCompatible;
    }
}
