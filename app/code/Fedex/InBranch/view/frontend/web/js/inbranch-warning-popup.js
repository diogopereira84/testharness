define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'loader'
], function ($, modal) {

  return {
    inBranchWarningPopup: function () {
      var options = {
        type: 'popup',
        responsive: true,
        clickableOverlay: true,
        modalClass: 'cart-warning-notification',
        buttons: [{
          text: '',
          class: 'actionclose'
        }],
      };
      $('.cart-warning-popup-main').modal(options).modal('openModal');
    }
  };
});
