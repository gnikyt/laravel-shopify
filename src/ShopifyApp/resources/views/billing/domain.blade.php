
@include('shopify-app::partials.polaris_skeleton_css')

<div>
    <div class="Polaris-SkeletonPage__Page" role="status" aria-label="Page loading">
        <div class="Polaris-SkeletonPage__Header">
            <div class="Polaris-SkeletonPage__TitleAndPrimaryAction">
                <div class="Polaris-SkeletonPage__TitleWrapper">
                    <div class="Polaris-SkeletonPage__SkeletonTitle"></div>
                </div>
            </div>
        </div>
        <div class="Polaris-SkeletonPage__Content">
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Section">
                            <div class="Polaris-SkeletonBodyText__SkeletonBodyTextContainer">
                            <div class="Polaris-SkeletonBodyText"></div>
                            <div class="Polaris-SkeletonBodyText"></div>
                            <div class="Polaris-SkeletonBodyText"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let paramsString = `shop=${sessionStorage.shopify_domain}&charge_id={{$charge_id}}`;
        window.location.href = `{!! $target !!}{!! Str::contains($target, '?') ? '&' : '?' !!}${paramsString}`;
    </script>
</div>

