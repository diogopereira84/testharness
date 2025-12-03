define([
  'Magento_Ui/js/lib/validation/validator',
  'jquery',
  'uiRegistry',
  'jquery-ui-modules/datepicker',
  'jquery/validate',
  'mage/translate',
], function (validator, $, uiRegistry) {
  "use strict";

  return function () {

    const INPUT_MAX_LENGTH = 30;
    const INPUT_MIN_LENGTH = 2;
    validator.addRule(
      'validate-input-name',
      function (value, componentName) {
        const component = uiRegistry.get(componentName);
        const isFocused = component.focused();

        // If the input doesn't have a minimum of 2 characters, then return false.
        if (value.length < INPUT_MIN_LENGTH && !isFocused) {
          return false;
        }

        // If the user typed more then 30 characters, then we should keep only the first 30 characters and remove the rest
        // If the input is focused, display the error message.
        if ( value.length > INPUT_MAX_LENGTH ) {
          component.value(value.substring(0, INPUT_MAX_LENGTH));
          if(isFocused) {
            return false;
          }
        }

        return true;
      },
      function() {
        return $.mage.__(window.checkoutConfig.input_name_error_message || "Please Enter between 2 and 30 characters.")
      }
    );

    validator.addRule(
        'validate-input-name-special-characters',
        function (value) {
            let nameRegex = /[$/@*()^!~#%&+]+/;
            let isD193257ToggleEnable = typeof window.checkoutConfig.explorers_d_193257_fix != 'undefined' ? window.checkoutConfig.explorers_d_193257_fix : false;
            if (isD193257ToggleEnable && (nameRegex.test(value))) {
                return false;
            }

            return true;
          },

          $.mage.__('Special characters are not allowed.')
      );

      validator.addRule(
          'validate-input-address-special-characters',
          function (value) {
              let nameRegex = /[/\\]+/;
              let isD193257ToggleEnable = typeof window.checkoutConfig.explorers_d_193257_fix != 'undefined' ? window.checkoutConfig.explorers_d_193257_fix : false;
              if (isD193257ToggleEnable && (nameRegex.test(value))) {
                  return false;
              }

              return true;
          },

          $.mage.__('Special characters are not allowed.')
      );
  };
});
