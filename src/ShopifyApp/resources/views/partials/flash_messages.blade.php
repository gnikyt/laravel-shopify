<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var Toast = actions.Toast;

        @if (request()->has('notice'))
            var toastNotice = Toast.create(app, {
                message: "{{ request()->get('notice') }}",
                duration: 3000,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif

        @if (request()->has('error'))
            var toastNotice = Toast.create(app, {
                message: "{{ request()->get('error') }}",
                duration: 3000,
                isError: true,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif
    });
</script>
