<?php

namespace OhMyBrew\ShopifyApp\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
        if (App::isProduction()) {
            // If in production mode, go to home with message
            return Redirect::route('login')->with('error', $this->getMessage());
        }
    }
}
