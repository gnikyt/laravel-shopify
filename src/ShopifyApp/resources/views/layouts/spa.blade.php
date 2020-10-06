<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ config('shopify-app.app_name') }}</title>

        @yield('styles')
    </head>

    <body>
        <div class="app-wrapper" id="app"></div>
        <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>
