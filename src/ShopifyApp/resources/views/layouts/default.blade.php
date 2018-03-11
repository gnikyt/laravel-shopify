<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('shopify-app.app_name') }}</title>

        @yield('styles')
    </head>

    <body>
        <div class="app-wrapper">
            <div class="app-content">
                <main role="main">
                    @yield('content')
                </main>
            </div>
        </div>

        @if(config('shopify-app.esdk_enabled'))
            <script src="https://cdn.shopify.com/s/assets/external/app.js?{{ date('YmdH') }}"></script>
            <script type="text/javascript">
                ShopifyApp.init({
                    apiKey: '{{ config('shopify-app.api_key') }}',
                    shopOrigin: 'https://{{ ShopifyApp::shop()->shopify_domain }}',
                    debug: false,
                    forceRedirect: true
                });
            </script>

            @include('shopify-app::partials.flash_messages')
        @endif

        @yield('scripts')
    </body>
</html>