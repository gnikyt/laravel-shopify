<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var Toast = actions.Toast;

        @if (session()->has('notice'))
            var toastNotice = Toast.create(app, {
                message: "{{ session('notice') }}",
                duration: 3000,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif

        @if (session()->has('error'))
            var toastNotice = Toast.create(app, {
                message: "{{ session('error') }}",
                duration: 3000,
                isError: true,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif
    });
</script>