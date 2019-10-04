<?php

namespace OhMyBrew\ShopifyApp\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;

/**
 * Base exception for all exceptions of the package.
 * Mainly to handle render in production.
 */
abstract class BaseException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request The incoming request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function render(Request $request)
    {
        if (!Config::get('shopify-app.debug')) {
            // If not in debug mode... show view
            return Redirect::route('login')->with('error', $this->getMessage());
        }
    }
}
