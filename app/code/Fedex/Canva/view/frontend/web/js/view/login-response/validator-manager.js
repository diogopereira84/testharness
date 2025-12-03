define([
    'jquery',
    'underscore',
    'Magento_Customer/js/customer-data',
    'fedex/storage',
    'domReady!'
], function ($, _, customerData,fxoStorage) {
    'use strict';

    return {
        command: {
            sso: customerData.get('sso_section'),
            fclStatusKey: 'fclPopupDisabled',
            fclSuccessCookieValue: $.mage.cookies.get('fcl_customer_login_success'),
            response: null
        },
        children: [],
        validate: function (command) {
            return this.children.every((item) => {
                return item.validate(command);
            });
        },
        getChild: function (index) {
            return this.children[index];
        },
        hasChildren: function () {
            return this.children.length > 0;
        },
        clear: function () {
            this.children = [];
        },
        add: function (validation) {
            this.children.push(validation);
        },
        remove: function (validation) {
            this.children = _.find(this.children, (item) => {
                return item !== validation;
            })
        },
        validateErrorResponse: {
            validate: function (command) {
                return command.response.success === 'error' || command.response.message === 'Logout Success';
            }
        },
        validateAuthModalDisabled: {
            validate: function (command) {
                if(window.e383157Toggle){
                    return !(typeof (fxoStorage.get(command.fclStatusKey)) != 'undefined'
                        && fxoStorage.get(command.fclStatusKey) != null
                        && fxoStorage.get(command.fclStatusKey) === 'true');
                }else{
                    return !(typeof (localStorage.getItem(command.fclStatusKey)) != 'undefined'
                        && localStorage.getItem(command.fclStatusKey) != null
                        && localStorage.getItem(command.fclStatusKey) === 'true');
                }
            }
        },
        validateAuthIsEnabled: {
            validate: function (command) {
                return !_.isEmpty(command.sso());
            }
        },
        validateFclLoginSuccess: {
            validate: function (command) {
                return command.fclSuccessCookieValue !== '1';
            }
        }
    };
});
