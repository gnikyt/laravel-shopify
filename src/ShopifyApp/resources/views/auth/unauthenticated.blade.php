@extends('shopify-app::layouts.default')

@section('scripts')
    @parent

    @if(config('shopify-app.appbridge_enabled'))
        <script>
            utils.getSessionToken(app).then((token) => {
                window.location.href = `{{ route(\Osiset\ShopifyApp\getShopifyConfig('route_names.authenticate')) }}?token=${token}`;
            });
        </script>
    @endif
@endsection
