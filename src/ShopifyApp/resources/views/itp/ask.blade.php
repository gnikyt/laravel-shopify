<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">

        <title>{{ config('shopify-app.app_name') }}</title>

        @include('shopify-app::partials.polaris_base_css')
    </head>

    <body>
        <main>
            <div class="Polaris-Page">
                <div class="Polaris-Page__Content">
                    <div class="Polaris-Layout">
                        <div class="Polaris-Layout__Section" id="attempt">
                            <div class="Polaris-Stack Polaris-Stack--vertical">
                                <div class="Polaris-Stack__Item">
                                    <div class="Polaris-Card">
                                        <div class="Polaris-Card__Header">
                                            <h1 class="Polaris-Heading">
                                                This app needs access to your browser data
                                            </h1>
                                        </div>
                                        <div class="Polaris-Card__Section">
                                            <p>
                                                Your browser is blocking this app from accessing your data, specifically setting session cookies.<br>To continue using this app, click <strong>Continue</strong>, then click <strong>Allow</strong> if the browser prompts you.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="Polaris-Stack__Item">
                                    <div class="Polaris-Stack Polaris-Stack--distributionTrailing">
                                        <div class="Polaris-Stack__Item">
                                            <button type="button" class="Polaris-Button Polaris-Button--primary"
                                                id="TriggerAllowCookiesPrompt">
                                                <span class="Polaris-Button__Content"><span>Continue</span></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="Polaris-Layout__Section Polaris-Card--hide" id="error">
                            <div class="Polaris-Stack Polaris-Stack--vertical">
                                <div class="Polaris-Stack__Item">
                                    <div class="Polaris-Card">
                                        <div class="Polaris-Card__Header">
                                            <h1 class="Polaris-Heading">
                                                Enable cookies
                                            </h1>
                                        </div>
                                        <div class="Polaris-Card__Section">
                                            <p>
                                                You must manually enable cookies in this browser in order to use this app within Shopify.<br>Click <strong>Continue</strong> once you've completed enabling cookies.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="Polaris-Stack__Item">
                                    <div class="Polaris-Stack Polaris-Stack--distributionTrailing">
                                        <div class="Polaris-Stack__Item">
                                            <button type="button" class="Polaris-Button Polaris-Button--primary"
                                                id="TriggerAllowCookiesPrompt2">
                                                <span class="Polaris-Button__Content"><span>Continue</span></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <script type="text/javascript">
            var tryBtn = 'TriggerAllowCookiesPrompt';
            var manualBtn = 'TriggerAllowCookiesPrompt2';
            var isSupported = document.requestStorageAccess !== undefined;

            /**
             * Handle swapping the request card with the manual card.
             */
            function swapCards() {
                var attemptCard = document.getElementById('attempt');
                var errorCard = document.getElementById('error');
                attemptCard.classList.add('Polaris-Card--hide');
                errorCard.classList.remove('Polaris-Card--hide');
            }

            /**
             * Handle accessing browser storage.
             */
            function handleStorageAccess(e, btn) {
                /**
                 * Redirect back to the app.
                 */
                var redirect = function () {
                    window.location.href = '{!! $redirect !!}';
                };

                /**
                 * Try and set items to the browser storage.
                 * Throw on error.
                 */
                var attempt = function () {
                    sessionStorage.setItem('itp', true);
                    document.cookie = 'itp=true; secure; SameSite=None';

                    if (!document.cookie) {
                        // Unable to set, storage must be blocked...
                        throw 'Cannot set third-party cookie.';
                    }

                    // Storage is OK... redirect back to home page of app
                    redirect();
                };

                /**
                 * Unable to access storage.
                 */
                var failure = function (error) {
                    // Show manual cookie card
                    console.warn('Storage access may be blocked.', error);
                    swapCards();
                };

                /**
                 * Common function for supported and unsupported browsers.
                 */
                var execute = function () {
                    if (btn === manualBtn) {
                        redirect();
                        return;
                    }

                    try {
                        attempt();
                    } catch (error) {
                        failure(error);
                    }
                };

                if (isSupported) {
                    // Supported... try it
                    document.requestStorageAccess().then(execute);
                } else {
                    // Unsupported... show manual cookie card
                    execute();
                }
            }

            if (!isSupported) {
                // Unsupported... show manual cookie card
                swapCards();
            }

            // Add event listeners
            for (var btn of [tryBtn, manualBtn]) {
                document.getElementById(btn).addEventListener('click', function (e) {
                    handleStorageAccess(e, btn);
                });
            }
        </script>
    </body>
</html>
