<?php

namespace OhMyBrew\ShopifyApp\Services;

use Exception;
use Jenssegers\Agent\Agent;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Helper for dealing with cookie and cookie issues.
 */
class CookieHelper
{
    use ConfigAccessible;

    /**
     * The HTTP agent helper.
     *
     * @var [type]
     */
    protected $agent;

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Sets the cookie policy.
     *
     * From Chrome 80+ there is a new requirement that the SameSite
     * cookie flag be set to `none` and the cookies be marked with
     * `secure`.
     *
     * Reference: https://www.chromium.org/updates/same-site/incompatible-clients
     *
     * Enables SameSite none and Secure cookies on:
     *
     * - Chrome v67+
     * - Safari on OSX 10.14+
     * - iOS 13+
     * - UCBrowser 12.13+
     *
     * @return void
     */
    public function setCookiePolicy(): void
    {
        $this->setConfig('session.expire_on_close', true);

        if ($this->checkSameSiteNoneCompatible()) {
            $this->setConfigArray([
                'session.secure'    => true,
                'session.same_site' => 'none',
            ]);
        }
    }

    /**
     * Checks to see if the current browser session should be
     * using the SameSite=none cookie policy.
     *
     * @return bool
     */
    public function checkSameSiteNoneCompatible(): bool
    {
        $compatible = false;

        try {
            $browser = $this->getBrowserDetails();
            $platform = $this->getPlatformDetails();

            if ($this->agent->is('Chrome') && $browser['major'] >= 67) {
                $compatible = true;
            }

            if ($this->agent->is('iOS') && $platform['major'] > 12) {
                $compatible = true;
            }

            if ($this->agent->is('OS X') &&
                ($this->agent->is('Safari') && !$this->agent->is('iOS')) &&
                $platform['float'] > 10.14
            ) {
                $compatible = true;
            }

            if ($this->agent->is('UCBrowser') &&
                $browser['float'] > 12.13
            ) {
                $compatible = true;
            }

            return $compatible;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns details about the current web browser.
     *
     * @return array
     */
    public function getBrowserDetails(): array
    {
        $version = $this->agent->version($this->agent->browser());
        $pieces = explode('.', str_replace('_', '.', $version));

        return [
            'major' => $pieces[0],
            'minor' => $pieces[1],
            'float' => (float) sprintf('%s.%s', $pieces[0], $pieces[1]),
        ];
    }

    /**
     * Returns details about the current operating system.
     *
     * @return array
     */
    public function getPlatformDetails(): array
    {
        $version = $this->agent->version($this->agent->platform());
        $pieces = explode('.', str_replace('_', '.', $version));

        return [
            'major' => $pieces[0],
            'minor' => $pieces[1],
            'float' => (float) sprintf('%s.%s', $pieces[0], $pieces[1]),
        ];
    }
}
