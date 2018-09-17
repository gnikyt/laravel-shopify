<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base target="_top">

        <title>Redirecting...</title>

        <script type="text/javascript" src="https://cdn.shopify.com/s/assets/external/app.js"></script>
        <script type="text/javascript">
            // If the current window is the 'parent', change the URL by setting location.href
            var redirectUrl = "{!! $authUrl !!}";
            if (window.top == window.self) {
                window.location.assign(redirectUrl);
            } else {
                ShopifyApp.redirect(redirectUrl);
            }
        </script>
    </head>
    <body>
    </body>
</html>