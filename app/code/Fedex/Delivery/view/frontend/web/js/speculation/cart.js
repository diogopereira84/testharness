require([
    'jquery',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'quote-login-modal'
],($, togglesAndSettings) => {
    'use strict';
    const homeUrl = $('.logo:first').attr('href');
    const checkoutURL = window.BASE_URL + 'checkout';
    let urls = [
        homeUrl,
        checkoutURL
    ];
    $('a[title="Home"]')
        .attr(
            'href',
            homeUrl
        );

    if (HTMLScriptElement.supports && HTMLScriptElement.supports("speculationrules")) {
        const specScript = document.createElement("script");
        specScript.type = "speculationrules";
        const specRules = {
            prerender: [
                {
                    source: "list",
                    urls: urls,
                    eagerness: "immediate"
                }
            ],
        };
        specScript.textContent = JSON.stringify(specRules);
        document.body.append(specScript);
    } else {
        for (let i in urls) {
            const linkElem = document
                .createElement("link");
            linkElem.rel = "prefetch";
            linkElem.href = urls[i];
            document.head.append(linkElem);
        }
    }

    if (!togglesAndSettings.tiger_team_D_225000) {
        $('body').append(
            $('<a />')
                .addClass('checkoutURL')
                .attr('href', checkoutURL)
                .hide()
        );

        $('.cart-to-checkout')
            .attr('onclick', 'jQuery(\'.checkoutURL\')[0].click();');
    }
});
