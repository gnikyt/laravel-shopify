<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \Osiset\ShopifyApp\Util::getShopifyConfig('app_name') }}</title>
    @include('shopify-app::partials.laravel_skeleton_css')
</head>

<body>
<div class="app-wrapper">
    <div class="app-content">
        <main role="main">
            <div class="app-wrapper">
                <div class="app-content">
                    <main role="main">
                        <div class="flex-center position-ref full-height">
                            <div class="content">
                                <p class="title">{{ config('shopify-app.app_name') }}</p>
                                <p class="m-t-0">Enter your Shopify domain to login.</p>

                                @if (session()->has('error'))
                                    <p>{{ session('error') }}</p>
                                @endif
                                <form class="form-horizontal" method="POST" action="{{ route('authenticate') }}">
                                    {{ csrf_field() }}
                                    <input type="text" name="shop" id="shop" placeholder="example.myshopify.com">
                                    <button type="submit">Login</button>
                                </form>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </main>
    </div>
</div>

@yield('scripts')
</body>
</html>
