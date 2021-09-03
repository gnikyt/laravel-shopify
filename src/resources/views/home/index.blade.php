@extends('shopify-app::layouts.default')

@section('styles')
    @include('shopify-app::partials.laravel_skeleton_css')
@endsection

@section('content')
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title m-b-md">
                Laravel &amp; Shopify
            </div>

            <p>Welcome to your Shopify App powered by Laravel.</p>
            <p>&nbsp;</p>
            <p>{{ $shop->name }}</p>
            <p>&nbsp;</p>

            <div class="links">
                <a href="https://github.com/osiset/laravel-shopify" target="_blank">Package</a>
                <a href="https://laravel.com" target="_blank">Laravel</a>
                <a href="https://github.com/osiset/laravel-shopify" target="_blank">GitHub</a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent

    @if(config('shopify-app.appbridge_enabled'))
        <script>
            actions.TitleBar.create(app, { title: 'Welcome' });
        </script>
    @endif
@endsection
