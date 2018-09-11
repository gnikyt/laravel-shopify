@extends('shopify-app::layouts.error')

@section('content')
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title m-b-md">Oops!</div>
                <p>{{ message }}</p>
            </div>
        </div>
    </div>
@endsection
