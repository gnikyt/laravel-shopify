<?php

namespace Osiset\ShopifyApp\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Responsible for authenticating the shop.
 */
class ItpController extends Controller
{
    public function handle(Request $request)
    {
        return Redirect::route('home', ['shop' => $request->query('shop')])
            ->withCookie(cookie('itp', true, 6000));
    }
}
