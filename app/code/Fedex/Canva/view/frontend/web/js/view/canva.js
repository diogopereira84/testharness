define([
    'ko',
    'jquery',
    'underscore',
    'mage/url',
    'Fedex_Canva/js/model/canva',
    'Magento_Customer/js/customer-data',
    'Fedex_Canva/js/view/login-response/validator-manager',
    'mage/cookies',
    'domReady!',
    'Fedex_SSO/js/view/customer',
    'Fedex_SSO/js/view/customer_mobile'
], function (ko, $, _, url, model, customerData, validatorManager) {
    'use strict';

    return {
        sso: customerData.get('sso_section'),
        customerLoginUrl: url.build('fcl/customer/login'),
        validatorManager: validatorManager,
        canvaSdkPromise: null,
        /**
         * @return {String}
         */
        getURL: function () {
            if ((model.getProcess() === model.process.EDITOR || model.getProcess() === model.process.EDIT) && !_.isEmpty(model.getArtwork().designId)) {
                return url.build(`canva?designId=${model.getArtwork().designId}`)
            }
            return url.build('canva');
        },
        /**
         * @return {Boolean}
         */
        isLoginEnabled: function () {
            return !_.isEmpty(this.sso());
        },
        /**
         * @return {Boolean}
         */
        hasEditingState: function () {
            return model.getProcess() === model.process.EDITOR && !_.isEmpty(model.getArtwork().designId);
        },
        /**
         * @return {Boolean}
         */
        hasCreatingState: function () {
            return model.getProcess() === model.process.CREATE && !_.isEmpty(model.getDesignOptions());
        },
        /**
         * @return {void}
         */
        validateCustomerLoginResponse: function () {
            this.validatorManager.clear();
            this.validatorManager.add(this.validatorManager.validateFclLoginSuccess);
            if (this.validatorManager.validate(this.validatorManager.command)) {
                $(document).trigger('canva:login:show');
            }
        },
        /**
         * @param {function} callbackFunction
         * @return {object}
         */
        loginRequest: function (callbackFunction) {
            this.validateCustomerLoginResponse();

            // Any other case, we should continue
            callbackFunction();
        },
        /**
         * @param {string} partnershipSdkUrl
         * @return {Promise}
         */
        loadCanvaPartnershipSdk: function (partnershipSdkUrl) {
            if(this.canvaSdkPromise) {
                return this.canvaSdkPromise;
            }

            this.canvaSdkPromise = new Promise((resolve) => {
                const firstScript = document.getElementsByTagName("script")[0];
                this.canvaScript = document.createElement("script");
                this.canvaScript.setAttribute("id", "design-script");
                this.canvaScript.src = partnershipSdkUrl;
                $(this.canvaScript).on('load', resolve);
                firstScript.parentNode.insertBefore(this.canvaScript, firstScript);
            });

            return this.canvaSdkPromise;
        },
        /**
         * @param {string} clientId
         * @param {string} partnerId
         * @param {string} userToken
         * @param {object} element
         * @return {Promise}
         */
        initializeCanvaPartnershipSdk: function (
            clientId,
            partnerId,
            userToken,
            element,
        ) {
            return window.Canva.Partnership.initialize({
                apiKey: clientId,
                partnerId: partnerId,
                container: element,
                autoAuthToken: userToken
            })
        },
    };
});
