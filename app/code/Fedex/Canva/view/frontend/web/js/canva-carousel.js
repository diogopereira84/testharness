define([
  'jquery',
  'mage/url',
  'Magento_Customer/js/customer-data',
  'Fedex_Canva/js/model/canva',
  'Fedex_Canva/js/view/canva',
  'js/ada',
  'jquery-ui-modules/widget'
], function($, url, customerData, canvaModel, canvaView) {
      'use strict';
      const CANVA_SDK_BODY_CLASS = 'CHM3WQ';

      $.widget('mage.canvaIframeCarousel', {

          options: {
              canva_section: null
          },

          _create: function () {
              if(customerData.canva_section) {
                  this._loadSdk(customerData)
                  return;
              }

              customerData.reload(['customer', 'canva_section'], false)
                .then((sections) => this._loadSdk(sections));
          },

          _loadSdk: function (sections) {
              this.options.canva_section = sections.canva_section;
              canvaView.loadCanvaPartnershipSdk(this.options.canva_section.partnershipSdkUrl)
                .then(() => this._initializeSdk(sections))
          },

          _initializeSdk: function(sections) {
              canvaView.initializeCanvaPartnershipSdk(
                sections.canva_section.clientId,
                sections.canva_section.partnerId,
                sections.canva_section.userToken,
                this.element[0]
              ).then(this._showCatalog.bind(this));
        },

          _showCatalog: function (canvaApi) {
              canvaApi.showCatalog({
                  tag: this.options.tag || null,
                  onProductSelect: (args) => this._openCanvaPage(args)
              });

              // CANVA_SDK_BODY_CLASS is added via Canva Partnership SDK and its preventing scrolling
              // we must remove it.
              $(document.body).removeClass(CANVA_SDK_BODY_CLASS);
          },

          _openCanvaPage: function (args) {
              canvaModel.resetProcess(canvaModel.process.CREATE, null, null, null, '/canva');
              canvaModel.setDesignOptions(args);
              sessionStorage.setItem('back-url', window.location.href);
              sessionStorage.removeItem('canva-reload');
              window.location.href = url.build('canva') + '?createNewDesign=true';
          }
      });

      return $.mage.canvaIframeCarousel;
});