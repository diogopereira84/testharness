define([
    'jquery',
    'ko',
    'underscore',
    'mage/template',
    'text!Fedex_Canva/templates/modal/modal-popup.html',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Fedex_Canva/js/model/canva',
    'Fedex_Canva/js/view/canva',
    'fedex/storage',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Fedex_SSO/js/view/customer'
], function($, ko, _, template, popupTpl, url, customerData, canvaModel, canvaView,fxoStorage) {

    //$.canvaLogin.modal;
    $.widget('canvaLogin.modal', $.mage.modal, {
        options: {
            popupTpl: popupTpl,
            customerData: customerData,
            canvaModel: canvaModel
        }
    });

    const CanvaLoginModal = {
        initModal: function(config, element) {
            $target = $(config.target);

            window.CANVA_REGISTER_REDIRECT_URL = ko.observable(config.buttons.register.href);
            window.CANVA_LOGIN_REDIRECT_URL = ko.observable(config.buttons.login.href);

            $(document).on('canva:login:show', $.proxy(function () {
                this.target.modal('openModal');
            }, this));

            const buttons = [];

            if(config.buttons.register.label) {
                buttons.push({
                    text: config.buttons.register.label,
                    class: 'primary-btn regster-btn common-btn',
                    click: function () {
                        window.location.href = window.CANVA_REGISTER_REDIRECT_URL();
                    }
                });
            }

            if(config.buttons.login.label) {
                buttons.push({
                    text: config.buttons.login.label,
                    class: 'secondary-btn login-btn common-btn',
                    click: function () {
                        window.location.href = window.CANVA_LOGIN_REDIRECT_URL();
                    }
                });
            }

            if(config.buttons.continue.label) {
                buttons.push({
                    text: config.buttons.continue.label,
                    class: 'continue-btn',
                    click: function () {
                        if(window.e383157Toggle){
                            fxoStorage.set('fclPopupDisabled',true);
                        }else{
                            localStorage.setItem('fclPopupDisabled',true);
                        }
                        $target.modal('closeModal');
                    }
                });
            }

            $target.modal({
                type: 'popup',
                modalClass: 'fedex-ddt-modal',
                trigger: '[data-trigger=canva-login-popup]',
                title: config.title,
                icon: config.icon,
                buttons: buttons,
            });
            this.target = $target;
        }
    };
    return {
        'canva-login-modal': CanvaLoginModal.initModal.bind(this)
    };
});
