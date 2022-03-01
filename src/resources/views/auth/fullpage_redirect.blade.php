<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base target="_top">

        <title>Redirecting...</title>

        <script src="https://unpkg.com/@shopify/app-bridge{{ \Osiset\ShopifyApp\Util::getShopifyConfig('appbridge_version') ? '@'.config('shopify-app.appbridge_version') : '' }}"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils{{ \Osiset\ShopifyApp\Util::getShopifyConfig('appbridge_version') ? '@'.config('shopify-app.appbridge_version') : '' }}"></script>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var redirectUrl = "{!! $authUrl !!}";
                if (window.top == window.self) {
                    // If the current window is the 'parent', change the URL by setting location.href
                    window.top.location.href = redirectUrl;
                } else {
                    // If the current window is the 'child', change the parent's URL with postMessage
                    normalizedLink = document.createElement('a');
                    normalizedLink.href = redirectUrl;

                    var AppBridge = window['app-bridge'];
                    var createApp = AppBridge.default;
                    var Redirect = AppBridge.actions.Redirect;
                    var app = createApp({
                        apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}",
                        shopOrigin: "{{ $shopDomain ?? Auth::user()->name }}",
                        host: "{{ \Request::get('host') }}",
                    });

                    var redirect = Redirect.create(app);
                    redirect.dispatch(Redirect.Action.REMOTE, normalizedLink.href);
                }
            });
        </script>
    </head>
    <body>
    </body>
</html>
