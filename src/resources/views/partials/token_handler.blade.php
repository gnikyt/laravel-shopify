<script data-turbolinks-eval="false">
    var SESSION_TOKEN_REFRESH_INTERVAL = {{ \Osiset\ShopifyApp\Util::getShopifyConfig('session_token_refresh_interval') }};
    var LOAD_EVENT = '{{ \Osiset\ShopifyApp\Util::getShopifyConfig('turbo_enabled') ? 'turbolinks:load' : 'DOMContentLoaded' }}';

    // Token updates
    document.addEventListener(LOAD_EVENT, () => {
        retrieveToken(app);
        keepRetrievingToken(app);
    });

    // Retrieve session token
    async function retrieveToken(app) {
        window.sessionToken = await utils.getSessionToken(app);

        // Update everything with the session-token class
        Array.from(document.getElementsByClassName('session-token')).forEach((el) => {
            if (el.hasAttribute('value')) {
                el.value = window.sessionToken;
                el.setAttribute('value', el.value);
            } else {
                el.dataset.value = window.sessionToken;
            }
        });

        const bearer = `Bearer ${window.sessionToken}`;
        if (window.jQuery) {
            // jQuery
            if (window.jQuery.ajaxSettings.headers) {
                window.jQuery.ajaxSettings.headers['Authorization'] = bearer;
            } else {
                window.jQuery.ajaxSettings.headers = { 'Authorization': bearer };
            }
        }

        if (window.axios) {
            // Axios
            window.axios.defaults.headers.common['Authorization'] = bearer;
        }
    }

    // Keep retrieving a session token periodically
    function keepRetrievingToken(app) {
        setInterval(() => {
            retrieveToken(app);
        }, SESSION_TOKEN_REFRESH_INTERVAL);
    }

    document.addEventListener('turbolinks:request-start', (event) => {
        var xhr = event.data.xhr;
        xhr.setRequestHeader('Authorization', `Bearer ${window.sessionToken}`);
    });
</script>
