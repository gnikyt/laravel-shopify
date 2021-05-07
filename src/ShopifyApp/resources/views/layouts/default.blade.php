<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \Osiset\ShopifyApp\getShopifyConfig('app_name') }}</title>
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

        @if(\Osiset\ShopifyApp\getShopifyConfig('appbridge_enabled'))
            <script src="https://unpkg.com/@shopify/app-bridge{{ \Osiset\ShopifyApp\getShopifyConfig('appbridge_version') ? '@'.config('shopify-app.appbridge_version') : '' }}"></script>
            <script src="https://unpkg.com/@shopify/app-bridge-utils{{ \Osiset\ShopifyApp\getShopifyConfig('appbridge_version') ? '@'.config('shopify-app.appbridge_version') : '' }}"></script>
            <script>
                AppBridge = window['app-bridge'];
                actions = AppBridge.actions;
                utils = window['app-bridge-utils'];
                createApp = AppBridge.default;
                app = createApp({
                    apiKey: "{{ \Osiset\ShopifyApp\getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}",
                    shopOrigin: "{{ $shopDomain ?? Auth::user()->name }}",
                    forceRedirect: true,
                });
            </script>
            @if(\Osiset\ShopifyApp\getShopifyConfig('turbo_enabled'))
                <script>
                    SESSION_TOKEN_REFRESH_INTERVAL = 2000;

                    // Token updates
                    document.addEventListener("turbolinks:load", (event) => {
                        retrieveToken(app);
                        keepRetrievingToken(app);
                    });

                    // Retrieve session token
                    async function retrieveToken(app) {
                        window.sessionToken = await utils.getSessionToken(app);
                    }

                    // Keep retrieving a session token periodically
                    function keepRetrievingToken(app) {
                        setInterval(() => {
                            retrieveToken(app);
                        }, SESSION_TOKEN_REFRESH_INTERVAL);
                    }

                    document.addEventListener("turbolinks:request-start", (event) => {
                        let xhr = event.data.xhr;
                        xhr.setRequestHeader("Authorization", "Bearer " + window.sessionToken);
                    });
                </script>
            @endif

            @include('shopify-app::partials.flash_messages')
        @endif

        @yield('scripts')
    </body>
</html>
