define([
    'jquery',
    'knockout',
    'uiComponent',
    'Fedex_MarketplaceUi/js/view/manage_toast_messages'
], function ($, ko, Component, toastMessages) {

    return Component.extend({

        currentUrl: window.location.href,

        initialize: function(config) {

            if (this.checkForUrlFlag()){

                window.history.pushState(
                    'object',
                    document.title,
                    this.currentUrl.split("?")[0]
                );

                toastMessages.addMessage(config.toast_message, true, false);

            }
        },

        checkForUrlFlag: function() {
            let currentUrlParams = new URL(this.currentUrl).searchParams;

            return Boolean(currentUrlParams.get('mktsellererror'));
        }
    });
});
