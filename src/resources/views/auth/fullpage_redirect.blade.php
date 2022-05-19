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
                if (window.top === window.self) {
                    // If the current window is the 'parent', change the URL by setting location.href
                    window.location.assign(redirectUrl)
                } else {
                    // If the current window is the 'child', change the parent's URL with postMessage

                    var AppBridge = window['app-bridge'];
                    var actions = AppBridge.actions;
                    var createApp = AppBridge.default;
                    var Redirect = actions.Redirect;
                    var app = createApp({
                        apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}",
                        host: "{{ \Request::get('host') }}",
                    });
                    Redirect.create(app).dispatch(Redirect.Action.REMOTE, redirectUrl);
                }
            });
        </script>
    </head>
    <body>
    </body>
</html>
