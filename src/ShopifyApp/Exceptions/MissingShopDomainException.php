<?php

namespace OhMyBrew\ShopifyApp\Exceptions;

use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;

/**
 * Exception for handling a missing shop's myshopify domain.
 */
class MissingShopDomainException extends Exception
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
            return Redirect::route('home')->with('error', $this->getMessage());
        }
    }
}
