<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        @if (session()->has('notice'))
            ShopifyApp.flashNotice("{{ session('notice') }}");
        @endif

        @if (session()->has('error'))
            ShopifyApp.flashError("{{ session('error') }}");
        @endif
    });
</script>