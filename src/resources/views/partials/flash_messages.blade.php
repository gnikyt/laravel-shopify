<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var Toast = actions.Toast;

        @if (request()->has('notice') || isset($flashNotice))
            var toastNotice = Toast.create(app, {
                message: "{{ request()->get('notice', $flashNotice ?? null) }}",
                duration: 3000,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif

        @if (request()->has('error') || isset($flashError))
            var toastNotice = Toast.create(app, {
                message: "{{ request()->get('error', $flashError ?? null) }}",
                duration: 3000,
                isError: true,
            });
            toastNotice.dispatch(Toast.Action.SHOW);
        @endif
    });
</script>
