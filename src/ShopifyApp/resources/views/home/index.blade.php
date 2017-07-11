@extends('shopify-app::layouts.default')

@section('content')
    <h1>Laravel Shopify</h1>
    <p>See documentation on overriding this template.</p>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        window.mainPageTitle = 'Main Page';
            ShopifyApp.ready(function(){
                ShopifyApp.Bar.initialize({
                    title: 'Welcome',
                    buttons: {
                    secondary: {
                        label: 'Documentation',
                        href: 'http://docs.shopify.com/embedded-app-sdk',
                        target: 'new'
                    }
                }
            });
        });
    </script>
@endsection