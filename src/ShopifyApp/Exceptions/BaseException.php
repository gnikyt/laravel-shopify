<?php

namespace OhMyBrew\ShopifyApp\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Base exception for all exceptions of the package.
 * Mainly to handle render in production.
 */
abstract class BaseException extends Exception
{
    use ConfigAccessible;

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request The incoming request.
     *
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function render(Request $request)
    {
        if (!$this->getConfig('debug')) {
            // If not in debug mode... show view
            return Redirect::route('login')->with('error', $this->getMessage());
        }
    }
}
