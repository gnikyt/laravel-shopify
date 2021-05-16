<script
    @if(\Osiset\ShopifyApp\getShopifyConfig('turbo_enabled'))
        data-turbolinks-eval="false"
    @endif
>
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

    if (isSafari) {
        sessionStorage.shopify_domain = "{{ $shopDomain ?? Auth::user()->name }}";
        [...document.querySelectorAll('a')].forEach((el) => {
            el.addEventListener('click', (event) => {
                let paramsString = `shop=${sessionStorage.shopify_domain}`;
                el.href = el.href + `${el.href.includes('?') ? '&' : '?'}${paramsString}`;
            });
        })
    }
</script>
