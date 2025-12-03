define([
    'jquery',
    'domReady',
    'consoleLogger',
    'jquery-ui-modules/widget',
    'mage/cookies'
], function ($, domReady, consoleLogger) {
    'use strict';
    let generateRandomString = null;
       generateRandomString = function(allowedCharacters, length) {
        let result = '';
        for (let i = 0; i < length; i++) {
          result += allowedCharacters.charAt(Math.floor(Math.random() * allowedCharacters.length));
        }
        return result;
      };


    let fedexFormKey = {

        /**
         * Creates widget 'mage.formKey'
         * @private
         */
        _create: function () {
            let formKey = $.mage.cookies.get('form_key'),
                options = {
                    Secure: window.cookiesConfig ? window.cookiesConfig.secure : false,
                    path:'/'
                };

            if (!formKey) {
                formKey = generateRandomString(this.options.allowedCharacters, this.options.length);
                $.mage.cookies.set('form_key', formKey, options);
            }
            $(this.options.inputSelector).val(formKey);
        }
    };

    return function (targetWidget) {
        $.widget('mage.formKey',  $.mage.formKey, fedexFormKey);
        return $.mage.formKey;
    };
});
