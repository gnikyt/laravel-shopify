<script data-turbolinks-eval="false">
    var SESSION_TOKEN_REFRESH_INTERVAL = 2000;
    var LOAD_EVENT = '{{ \Osiset\ShopifyApp\getShopifyConfig('turbo_enabled') ? 'turbolinks:load' : 'DOMContentLoaded' }}';

    // Token updates
    document.addEventListener(LOAD_EVENT, (event) => {
        retrieveToken(app);
        keepRetrievingToken(app);
    });

    // Retrieve session token
    async function retrieveToken(app) {
        window.sessionToken = await utils.getSessionToken(app);

        // Update everything with the session-token class
        [...document.getElementsByClassName('session-token')].forEach((el) => {
            if (el.hasAttribute('value')) {
                el.value = window.sessionToken;
            } else {
                el.dataset.value = window.sessionToken;
            }
        });

        if (window.jQuery) {
            // jQuery
            window.jQuery.ajaxSettings.headers['Authorization'] = `Bearer ${window.sessionToken}`;
        }

        if (window.axios) {
            // Axios
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${window.sessionToken}`;
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
