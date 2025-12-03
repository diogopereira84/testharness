define([
    'jquery',
    'ko',
    'text!Fedex_SSO/templates/modal/modal-popup.html',
    'Magento_Ui/js/modal/modal'
], function($,ko, fclPopupTpl) {

    $.widget('fclLogin.modal', $.mage.modal, {
        options: {
            fclPopupTpl: fclPopupTpl
        }
    });

    const isInitialized = ko.observable(false);

    const FCLLoginModal = {
        initModal: function(config, element) {
            $target = $(config.target);
            $target.modal({
                modalClass: 'login-poup-model',
                trigger: '[data-trigger=login-popup]',
                title: config.title,
                buttons: []
            });
            $('.login-poup-model').attr('aria-label','Login');
            this.target = $target;
            isInitialized(true);
        }
    };
    return {
        'fcl-login-modal': FCLLoginModal.initModal.bind(this),
        isInitialized: isInitialized
    };
});