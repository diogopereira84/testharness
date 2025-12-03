define([
   'jquery',
   'underscore',
   'jquery-ui-modules/widget',
   'mage/translate'
], function ($, _) {
   'use strict';

   $.widget('mage.fedexAccount', {

      options: {
      },

      /** @inheritdoc */
      _create: function () {
          this.accountNo = $(this.options.accountNoSelector);
          this.removeAccountNo = $(this.options.removeAccountNoSelector);
          this.inputAccountNo = $(this.options.inputAccountNumber);

         $(this.inputAccountNo).on('keypress',$.proxy(function (evt) {
               evt = (evt) ? evt : window.event;
               var charCode = (evt.which) ? evt.which : evt.keyCode;
               if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                  return false;
               }
               return true;
         }, this));

         $(this.inputAccountNo).on('paste', $.proxy(function (event) {
            if (event.originalEvent.clipboardData.getData('Text').match(/[^\d]/)) {
               event.preventDefault();
            }
         }, this));

         $(this.options.applyButton).on('click', $.proxy(function () {
            this.accountNo.attr('data-validate', '{required:true}');
            if($('#fedex_account_no').val() == ''){
               $('#account-no-error').css('display', 'block');
               setTimeout(function() {
                  $('#account-no-error').fadeOut('slow');
               }, 2000);
               return false;
            }
            this.removeAccountNo.attr('value', '0');
            $('.action.apply-account.primary.account-no').css('color', '#fff');
            $('#apply-account-loader').show();
            $(".account-no").trigger('processStart'); 
            $(this.element).validation().submit();
         }, this));

         $(this.options.cancelButton).on('click', $.proxy(function () {
              this.accountNo.removeAttr('data-validate');
              $('.action.apply-account.primary.account-no').css('color', '#fff');
              $('#apply-account-loader').show();
              this.accountNo.attr('value', '1');
              this.element.submit();
         }, this));
         $('#fedex_account_no').on('blur', function () {
            var acc = $('#fedex_account_no').val();
            if (acc.length > 1) {
                var masked = acc.length > 4 ? "*" + acc.substr(-4) : acc;
                $('#fedex_account_no_hidden').attr("value", acc);
                $('#fedex_account_no').attr("value", masked);
            }
        });
        $('#fedex_account_no').on('focus', function () {
            let acc = $('#fedex_account_no').val();
            if(acc) {
               var accForHidden = $('#fedex_account_no_hidden').val();
               if (accForHidden.length > 1) {
                  $('#fedex_account_no').attr("value",accForHidden);
               }
            }
         });
      }	

   });

   return $.mage.fedexAccount;

});
