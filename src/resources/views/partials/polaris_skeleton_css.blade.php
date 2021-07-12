<style type="text/css">
    @keyframes shimmer {
        0% {
            opacity: .45;
        }
        to {
            opacity: .9;
        }
    }

    body {
        min-height: 100%;
        height: 100%;
        font-size: 1.4rem;
        font-weight: 400;
        line-height: 2rem;
        text-transform: initial;
        letter-spacing: initial;
        font-weight: 400;
        color: #212b36;
        font-family: -apple-system, BlinkMacSystemFont, San Francisco, Roboto,
            Segoe UI, Helvetica Neue, sans-serif;
    }

    @media (min-width: 40em) {

        html,
        body {
            font-size: 1.4rem;
        }
    }

    html {
        position: relative;
        font-size: 62.5%;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
        text-size-adjust: 100%;
        text-rendering: optimizeLegibility;
    }

    body {
        min-height: 100%;
        margin: 0;
        padding: 0;
        background-color: #f4f6f8;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p {
        margin: 0;
        font-size: 1em;
        font-weight: 400;
    }

    .Polaris-Layout {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        -webkit-box-align: start;
        -ms-flex-align: start;
        align-items: flex-start;
        margin-top: -2rem;
        margin-left: -2rem;
    }

    .Polaris-Layout__Section {
        -webkit-box-flex: 2;
        -ms-flex: 2 2 48rem;
        flex: 2 2 48rem;
        min-width: 51%;
        max-width: calc(100% - 2rem);
        margin-top: 2rem;
        margin-left: 2rem;
    }

    .Polaris-SkeletonPage__Page {
        margin: 0 auto;
        padding: 0;
        max-width: 99.8rem;
    }

    @media (min-width: 30.625em) {
        .Polaris-SkeletonPage__Page {
            padding: 0 2rem;
        }
    }

    .Polaris-SkeletonPage__Header {
        padding: 1.6rem 1.6rem 0;
        padding-bottom: .8rem;
    }

    @media (min-width: 30.625em) {
        .Polaris-SkeletonPage__Header {
            padding-left: 0;
            padding-right: 0;
        }
    }

    .Polaris-SkeletonPage__TitleWrapper {
        flex: 1 1;
    }

    .Polaris-SkeletonPage__SkeletonTitle {
        animation: shimmer .8s linear infinite alternate;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        will-change: opacity;
        position: relative;
        max-width: 12rem;
        height: 2.8rem;
    }

    .Polaris-SkeletonPage__SkeletonTitle:after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: block;
        border-radius: 3px;
        background-color: rgb(228, 229, 231);
    }

    .Polaris-SkeletonPage__PrimaryAction {
        align-self: stretch;
    }

    @media (min-width: 30.625em) {
        .Polaris-SkeletonPage__PrimaryAction {
            margin-top: .8rem;
            margin-bottom: -.8rem;
        }
    }

    @media (max-width: 50em) {
        .Polaris-SkeletonPage__PrimaryAction {
            margin-top: 1.6rem;
            margin-bottom: -.8rem;
        }
    }

    .Polaris-SkeletonPage__PrimaryAction > * {
        height: 3.6rem;
        min-width: 10rem;
    }

    @media (min-width: 40em) {
        .Polaris-SkeletonDisplayText--sizeLarge {
            height: 3.2rem;
        }
    }

    .Polaris-SkeletonDisplayText--sizeLarge {
        height: 2.8rem;
    }

    .Polaris-SkeletonDisplayText__DisplayText {
        max-width: 12rem;
        animation: shimmer .8s linear infinite alternate;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        will-change: opacity;
        position: relative;
    }

    .Polaris-SkeletonDisplayText__DisplayText:after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: block;
        border-radius: 3px;
        background-color: rgb(228, 229, 231);
    }

    .Polaris-SkeletonPage__Actions {
        margin-top: .8rem;
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        align-items: center;
    }

    .Polaris-SkeletonPage__Content {
        margin: 1.6rem 0;
    }

    .Polaris-Card {
        overflow: hidden;
        background-color: white;
        box-shadow: 0 0 0 1px rgba(63, 63, 68, 0.05),
            0 1px 3px 0 rgba(63, 63, 68, 0.15);
    }

    .Polaris-Card + .Polaris-Card {
        margin-top: 2rem;
    }

    @media (min-width: 30.625em) {
        .Polaris-Card {
            border-radius: 3px;
        }
    }

    .Polaris-Card__Section {
        padding: 2rem;
    }

    .Polaris-Card__Section + .Polaris-Card__Section {
        border-top: 1px solid #dfe3e8;
    }


    .Polaris-SkeletonBodyText__SkeletonBodyTextContainer {
        animation: shimmer .8s linear infinite alternate;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        will-change: opacity;
    }

    .Polaris-SkeletonBodyText {
        height: .8rem;
        position: relative;
    }

    .Polaris-SkeletonBodyText + .Polaris-SkeletonBodyText {
        margin-top: 1.2rem;
    }

    .Polaris-SkeletonBodyText:after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: block;
        background-color: rgb(228, 229, 231);
        border-radius: 3px;
    }
</style>
