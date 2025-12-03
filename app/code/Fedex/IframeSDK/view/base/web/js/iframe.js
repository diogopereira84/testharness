var defineObj;
if (window.location.href.indexOf("catalog/product") > -1) {
  defineObj = ['loadIframe','','','','','Magento_Ui/js/modal/modal','jquery','loader','fedex/storage'];
} else {
  defineObj = ['loadIframe','add','peProductProductionController','Magento_Customer/js/customer-data','Fedex_Canva/js/model/canva','Magento_Ui/js/modal/modal','jquery','loader','fedex/storage'];
}
define(defineObj, function (loadIframe, add, peProductProductionController, customerData, canvaModel, modal, $,loader,fxoStorage) {
    if (!(window.location.href.indexOf("catalog/product") > -1)) {
        $(function() {
            $("body").loader("show");
        });
    }
  return function (config) {
    (function () {
      let uploadFilePODData;
      if(window.e383157Toggle){
          uploadFilePODData = fxoStorage.get("pod-data");
      }else{
          uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
      }
      if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin){
        const requiredParams = ['productType', 'siteName'];
        const queryParams = getQueryParamList((window.location && window.location.search));
        const paramMap = handleMagentoPODParams(getParamMap(queryParams));
        const instanceId = getInstanceId(paramMap);
        const container = document.querySelector('.container');
        const itemToEdit = getItemToEdit(instanceId);
        const id = determineProductId(itemToEdit, paramMap);
        let environment = {env: 'FROMCONFIG'};
        const podData = paramMap;
          let uploadFilePODData;
          if(window.e383157Toggle){
              uploadFilePODData = fxoStorage.get("pod-data");
          }else{
              uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
          }
        const initConfig = configureInitConfig(id, container, environment.env, paramMap, requiredParams);
        loadIframe(
          initConfig,
          getFxoProduct,
          successCallback,
          errorCallback,
          itemToEdit && uploadFilePODData.isEdit ? itemToEdit : null,
          podData
        );
      } else {
        customerData.reload(['cart', 'customer', 'sso_section'], false).then((sections) => {
          const requiredParams = ['productType', 'siteName'];
          const container = document.querySelector('.container');
          const queryParams = getQueryParamList((window.location && window.location.search));
          const paramMap = handleMagentoPODParams(getParamMap(queryParams));
          const instanceId = getInstanceId(paramMap);
          const itemToEdit = getItemToEdit(instanceId);
          const id = determineProductId(itemToEdit, paramMap);
          let environment = {env: 'FROMCONFIG'};
          const initConfig = configureInitConfig(id, container, environment.env, paramMap, requiredParams);
          const podData = paramMap;

          let expressCheckout = expressCheckoutToggle();
          if (expressCheckout) {
            podData.isExpressCheckout = true;
          }

          podData.showDiscountBreakdown = true;
          let storeCode = typeof (window.checkout.store_code) !== "undefined" && window.checkout.store_code !== null ? window.checkout.store_code : false;
          let isFclCustomer = typeof (window.checkout.is_fcl_customer) !== "undefined" && window.checkout.is_fcl_customer !== null ? window.checkout.is_fcl_customer : false;
          let uploadFileStoragePODData;
          if(window.e383157Toggle){
              uploadFileStoragePODData = fxoStorage.get("pod-data");
          }else{
              uploadFileStoragePODData = JSON.parse(localStorage.getItem("pod-data"));
          }
          let isAdminProductCreation = uploadFileStoragePODData && uploadFileStoragePODData.fromAdmin !== undefined && uploadFileStoragePODData.fromAdmin;

          let isUploadToQuoteEnable = (!isAdminProductCreation && typeof (window.checkout.xmen_upload_to_quote) !== "undefined" && window.checkout.xmen_upload_to_quote !== null) ? window.checkout.xmen_upload_to_quote : false;
          let uploadToQuoteConfigValues = (!isAdminProductCreation && typeof (window.checkout.upload_to_quote_config_values) !== "undefined" && window.checkout.upload_to_quote_config_values !== null) ? window.checkout.upload_to_quote_config_values : false;

          if (isUploadToQuoteEnable) {
            podData.uploadToQuote = JSON.parse(uploadToQuoteConfigValues);
          }

          if (!isFclCustomer && storeCode == 'default') {
            let ssoSection = customerData.get('sso_section')();
            let fedexAccount = ssoSection.fedex_account_number==''?null:ssoSection.fedex_account_number;
            podData.fedexAccount = fedexAccount;

            loadIframe(
              initConfig,
              getFxoProduct,
              successCallback,
              errorCallback,
              itemToEdit && window.location.search.includes('edit') ? itemToEdit : null,
              podData
            );
          } else {
              let ssoSection = customerData.get('sso_section')();
              let fedexAccount = ssoSection.fedex_account_number;
              podData.fedexAccount = fedexAccount;

            loadIframe(
              initConfig,
              getFxoProduct,
              successCallback,
              errorCallback,
              itemToEdit && window.location.search.includes('edit') ? itemToEdit : null,
              podData
            );
          }
        });
      }
    })();

    /*
     * Identify express checkout toggle
     */
    function expressCheckoutToggle() {
      let isFclCustomer = typeof (window.checkout.is_fcl_customer) !== "undefined" && window.checkout.is_fcl_customer !== null ? window.checkout.is_fcl_customer : false;
      let isCommercial = typeof (window.checkout.is_commercial) !== "undefined" && window.checkout.is_commercial !== null ? window.checkout.is_commercial : false;
      let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';

      let match = document.cookie.match(new RegExp('(^| )'+fclCookieConfigVal+'=([^;]+)'));
      if (!isFclCustomer && match && match[2].toLowerCase() != 'no' && !isCommercial) {
        isFclCustomer = true;
      }

      return isFclCustomer;
    }

    /* logic to show warning popup for mix cart product*/
    function warningPopup(fxoProduct) {
      var options = {
        type: 'popup',
        responsive: true,
        clickableOverlay: true,
        modalClass: 'cart-warning-notification',
        buttons: [{
           text: '',
           class: 'action-close'
        }],
        closed: function () {
          const iframeEl = document.getElementById('fedex_iframe');
          iframeEl.contentWindow.postMessage({
              type: "magento-mixed-cart",
              data: {
                enableAddToCart: true,
                productConfig: fxoProduct.fxoProductInstance.productConfig
              }
          }, '*');
        }
      };
      if ($(".cart-warning-popup-main").length > 0 && $(".cart-warning-notification").length == 0) {
        $('.cart-warning-popup-main').modal(options).modal('openModal');
      } else {
        $('.cart-warning-popup-main').modal('openModal');
      }
    }

    function setPOD2EditableCustomerAdmin(fxoProduct) {
        if(window.e383157Toggle){
            fxoStorage.set("configurator_json",fxoProduct);
        }else{
            window.localStorage.setItem("configurator_json",JSON.stringify(fxoProduct));
        }
      let options = {
          type: 'popup',
          responsive: true,
          modalClass: 'shared-catalog-setting-modal',
          innerScroll: true,
          buttons: [],
          close: [],
      };

      $('#setting-formp-popup').modal(options, $('#setting-formp-popup')).modal('openModal');
      if (fxoProduct.fxoProductInstance.productConfig.product.userProductName != undefined ) {
          let userProductname = fxoProduct.fxoProductInstance.productConfig.product.userProductName;
        $("#shared-catalog-setting-name").val(userProductname);
      }
      if (fxoProduct.fxoProductInstance.productRateTotal.price != undefined ) {
        let productPrice = JSON.stringify(fxoProduct.fxoProductInstance.productRateTotal.priceAfterDiscount);
        $("#shared-catalog-setting-price").val(productPrice);
      }
      if(fxoProduct.fxoMenuId != undefined) {
        $("#catalogmvp_fxo_menu_id").val(fxoProduct.fxoMenuId);
      }
      $("#shared-catalog-setting-externalproduct").val(JSON.stringify(fxoProduct.fxoProductInstance.productConfig.product));
      if(fxoProduct.fxoProductInstance.productConfig.customDocumentDetails != undefined) {
        $("#customization_fields").val(JSON.stringify(fxoProduct.fxoProductInstance.productConfig.customDocumentDetails));
      }

    }

    // Download field for configured product
    function downloadFieldForConfProd() {
      jQuery('div[data-index="name"]').after("<div class='admin__field download-field download-field-configured'><div class='admin__field-label configure-product-pod-lable'><label><span>Download File(s)</span></label></div><div class='admin__field-control'><img src='/media/wysiwyg/catalogmvp/download-blue.png' alt='Download File(s)' class='download-configure-img' style='cursor: pointer;' /></div></div>");
    }

    function setPOD2Editable(fxoProduct){
      if (fxoProduct) {
        if (fxoProduct.fxoProductInstance.productRateTotal.price != undefined ) {
          let productPrice = JSON.stringify(fxoProduct.fxoProductInstance.productRateTotal.priceAfterDiscount);
          productPrice = productPrice.replace("$",'');
          productPrice = productPrice.replace('"','');
          productPrice = productPrice.replace('"','');
          jQuery("input[name='product[price]']").val(productPrice).change();
          if (fxoProduct.fxoProductInstance.productConfig.product.userProductName != undefined ) {
            let userProductname = fxoProduct.fxoProductInstance.productConfig.product.userProductName;
            $("input[name='product[name]").val(userProductname).change();
          }
        }
        if(fxoProduct.fxoMenuId != undefined) {
          jQuery("input[name='extraconfiguratorvalue[fxo_menu_id]").val(fxoProduct.fxoMenuId).change();
        }
        jQuery("textarea[name='product[external_prod]']").val(JSON.stringify(fxoProduct.fxoProductInstance.productConfig.product)).change();


          if(jQuery("textarea[name='product[external_prod]']").val().length !== 0){
            if (jQuery('.edit-product-pod').length === 0) {
              jQuery('div[data-index="attribute_set_id"]')
                  .after(
                      "<div class='admin__field edit-product-pod'>" +
                      "<div class='admin__field-label edit-product-pod-lable'>" +
                      "<label>" +
                      "<span>Configure Product</span>" +
                      "</label>" +
                      "</div>" +
                      "<div class='admin__field-control'>" +
                      "<button id='edit-product' " +
                      "title='Edit Product' " +
                      "class='action-default primary edit-button mvp-catalog-edit-button'>" +
                      "<span>EDIT</span>" +
                      "</button>" +
                      "</div>" +
                      "</div>"
                  );
              if (jQuery('body').hasClass('mazegeeks_download_catalog_items')) {
                downloadFieldForConfProd();
              }
            }
          }
          if (jQuery('.configure-product-pod').length !== 0) {
              jQuery(".configure-product-pod").remove();
              jQuery(".download-field-non-configured").remove();
          }
      }
      $('#iframe_popup').modal("closeModal");
    }

    /* logic to open mix cart validation popup based on productionGroup for FXO, RPI and Navitor*/
    function getFxoProduct(fxoProduct) {

        let uploadFilePODData;
        if(window.e383157Toggle){
            uploadFilePODData = fxoStorage.get("pod-data");
        }else{
            uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
        }
      if(uploadFilePODData && uploadFilePODData.fromCustomerAdmin !== undefined && uploadFilePODData.fromCustomerAdmin){
        setPOD2EditableCustomerAdmin(fxoProduct);
      }
      else if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin){
        setPOD2Editable(fxoProduct);
      } else {
        if (!window.checkout.is_commercial) {
          var productInstanceValue = [];
          if (typeof (fxoProduct.fxoProductInstance.productConfig.product) !== "undefined" && (fxoProduct.fxoProductInstance.productConfig.product != null)) {
            productInstanceValue.push(fxoProduct.fxoProductInstance.productConfig.product);
          }
          if (customerData.get('cart')) {
            const cart = customerData.get('cart');
            let productInstance;
            var mageCacheStorageValue = [];
            if(cart){
                if (cart().items && cart().items.length > 0) {
                    productInstance = cart().items.find((item) => {
                        if (!item.is_third_party_product) {
                            var externalProductInstance;
                            if (typeof item.externalProductInstance === 'object') {
                                externalProductInstance = item.externalProductInstance;
                                productInstanceValue.push(externalProductInstance);
                            } else {
                                externalProductInstance = JSON.parse(item.externalProductInstance);
                                productInstanceValue.push(externalProductInstance.fxoProductInstance.productConfig.product);
                            }
                            if (typeof (externalProductInstance.instanceId) !== "undefined" && (externalProductInstance.instanceId != null)) {
                                mageCacheStorageValue.push(externalProductInstance.instanceId);
                            } else {
                                mageCacheStorageValue.push(externalProductInstance.fxoProductInstance.id);
                            }
                        }
                    });
                }
            }

          }
          var productProductionController = new peProductProductionController.ProductionController(window.checkout.mix_cart_product_engine_url);

          var productMixCartValidation = productProductionController.getProductionGroups(productInstanceValue,
            function (result) {
              getMixCartValidation(result, fxoProduct, mageCacheStorageValue);
            },
            function (error) {
              getMixCartError(error);
            }
          );
        } else {
          add(fxoProduct);
        }
      }
    }

    /* Return success result from getProductionGroups function*/
    function getMixCartValidation(result, fxoProduct, mageCacheStorageValue) {
      var mixCartValidation = result;
      var productionGroups = [];
      $.each(JSON.parse(JSON.stringify(mixCartValidation)), function (i, item) {
        productionGroups.push(item.productionGroup);
      });
      if ($.uniqueSort(productionGroups).length > 1) {
	  if (mageCacheStorageValue.length == 1) {
            var currentProductInstance = "";
            if (typeof(fxoProduct.fxoProductInstance) != "undefined" && fxoProduct.fxoProductInstance != null) {
              var currentProductInstance = fxoProduct.fxoProductInstance.id;
            }
            var cartProductInstanceId = mageCacheStorageValue[0];
            if (cartProductInstanceId == currentProductInstance) {
              add(fxoProduct);
            } else {
              warningPopup(fxoProduct);
            }
          } else {
            warningPopup(fxoProduct);
	  }
      } else {
          add(fxoProduct);
      }
    }

    /* Return error result from getProductionGroups function*/
    function getMixCartError(error) {
      console.log(error);
    }

    function successCallback(info) {
      const spinner = document.querySelector('.lds-ring');
      spinner.style.display = 'none';
    }

    function errorCallback(error) {
      console.log(error);
    }

    function configureInitConfig(id, containerEl, env, paramMap, requiredParams) {
      let initConfig = {
        containerEl,
        env,
        id,
      }
      for (const param of requiredParams) {
        if (!(param in paramMap)) {
          throw new Error(`Missing required param needed for configuration object: ${param}`);
        }

        initConfig[param] = paramMap[param];
      }
      return initConfig;
    }

    function determineProductId(itemToEdit = null, paramMap) {
      let id = null;
      if (paramMap) {
        if ('id' in paramMap) {
          id = paramMap['id'];
        } else if (('edit' in paramMap) && itemToEdit && itemToEdit.fxoMenuId) {
          id = itemToEdit.fxoMenuId;
        }
      }
      if (!id) {
        // default to custom product id.
        id = '1534436209752-2';
      }
      return id;
    }

    function getQueryParamList(search) {
      if (!search) return null;

      return search.replace('?', '').replace('/', '').split('&');
    }

    function getParamMap(queryParams) {
      if (!queryParams || queryParams.length < 1) return null;
      const paramMap = {};
      queryParams.forEach((param) => {
        const [key, value] = param.split('=');
        paramMap[key] = value;
      });

      return paramMap;
    }

    function getInstanceId(paramMap) {
      if (!paramMap) return null;
      let instanceId = null;
      if ('edit' in paramMap) {
        instanceId = paramMap['edit'];
      }
      return instanceId;
    }

    function getItemToEdit(instanceId) {
      // if coming from admin, return externl_prod json
        let uploadFilePODData;
        if(window.e383157Toggle){
            uploadFilePODData = fxoStorage.get("pod-data");
        }else{
            uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
        }
      if(uploadFilePODData && uploadFilePODData.fromCustomerAdmin !== undefined && uploadFilePODData.fromCustomerAdmin){
        let externalProd;
        if(window.e383157Toggle){
            externalProd = fxoStorage.get("adminconfig");
        }else{
            externalProd = JSON.parse(localStorage.getItem("adminconfig"));
        }
        if (externalProd) {
          return externalProd;
        }
      }
      else if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin){
          let externalProd;
          if(window.e383157Toggle){
              externalProd = fxoStorage.get("adminconfig");
          }else{
              externalProd = JSON.parse(localStorage.getItem("adminconfig"));
          }
        if (externalProd) {
            if (window.e383157Toggle) {
                fxoStorage.set("adminconfig", null);
            } else {
                localStorage.setItem("adminconfig", null);
            }
          return JSON.parse(externalProd.instance);
        }
      }

      if (!instanceId) return;
      const cart = customerData.get('cart');
      let productInstance;
      productInstance = cart().items.find((item) => {
        if (item.product_attributesetname === 'FXOPrintProducts' && !!item.externalProductInstance) {
          return typeof item.externalProductInstance === 'object' ?
              item.externalProductInstance.instanceId.toString() === instanceId.toString() :
              JSON.parse(item.externalProductInstance).instanceId.toString() === instanceId.toString();
        }
      });
      if (productInstance) {
        productInstance =  typeof productInstance.externalProductInstance === 'object' ?
            productInstance.externalProductInstance :
            JSON.parse(productInstance.externalProductInstance);
      }
      return productInstance;
    }

    /**
     * Handles params overriding for Magento-PoD iframe functionality.
     * @param {Object} queryParams - params parsed inside Magento PoD Iframe URL
     */
    function handleMagentoPODParams(queryParams) {
      const urlData = queryParams;
        let uploadFilePODData;
        if(window.e383157Toggle){
            uploadFilePODData = fxoStorage.get("pod-data");
        }else{
            uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
        }

      let cloudDriveIntegrationFlag = 0;

      let sessionData = "";
      let canvaProduct = "";
      let isProductAvailable = "";
      let cloudDriveIntegrationFlagInfo = "";

      if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin){
        isProductAvailable = !_.isEmpty(canvaProduct);
      } else {
        customerData.reload(['cloud_drive_integration_flag']);
	      
        cloudDriveIntegrationFlagInfo = customerData.get('cloud_drive_integration_flag')();
        sessionData = canvaModel.getPod();
        canvaProduct = canvaModel.getProduct();
        isProductAvailable = !_.isEmpty(canvaProduct);

      }
      if (typeof(cloudDriveIntegrationFlagInfo) !== 'undefined' &&
                    cloudDriveIntegrationFlagInfo !== null) {
        cloudDriveIntegrationFlag = cloudDriveIntegrationFlagInfo;
      }

      if (_.isEmpty(sessionData)) {
        if(!_.isEmpty(uploadFilePODData)) {
          sessionData = uploadFilePODData;
        } else {
          sessionData = '{"id":"","siteName":"","accessToken":"","productType":"","contentAssociation":{},"productPageToGoBack":""}';
        }
      }

      if (!_.isEmpty(canvaProduct.designId)) {
        $(document).trigger('canva:backlink:show');
      }

      // Calling PoD iframe without previous data setup and with missing query parameters
      if (urlData && urlData.id && !urlData.accessToken) {
        processedParams = {
          id: urlData.id,
          accessToken: config.fallbackAccessToken,
          productType: 'PRINT_PRODUCT',
          waitForContentAssociation: waitForContentAssociation,
          productPageToGoBack: config.fallbackProductPageToGoBack,
          pod_workflow_type: 'MAGENTO_FLOW',
          siteName: "",
          cloudDriveIntegrationFlag:cloudDriveIntegrationFlag
        }
      }

      else if (urlData && urlData.id && urlData.accessToken) {
        processedParams = {
          id: urlData.id,
          siteName: urlData.siteName,
          accessToken: urlData.accessToken,
          productType: urlData.productType,
          contentAssociation: null,
          productPageToGoBack: function () { },
          pod_workflow_type: 'MAGENTO_FLOW',
          canvaProduct: null,
          cloudDriveIntegrationFlag:cloudDriveIntegrationFlag
        }
      }

      // Calling PoD iframe with previous initialization
      else if (sessionData.id) {
        processedParams = {
          id: sessionData.id,
          siteName: sessionData.siteName,
          accessToken: sessionData.accessToken,
          productType: sessionData.productType,
          contentAssociation: sessionData.contentAssociation,
          canvaProduct: canvaProduct,
          productPageToGoBack: sessionData.productPageToGoBack,
          pod_workflow_type: 'MAGENTO_FLOW',
          cloudDriveIntegrationFlag:cloudDriveIntegrationFlag
        }
      }

      // Calling PoD without any configuration (flows will not work but iframe will be loaded)
      else if ((config && config.fallbackAccessToken) || (isProductAvailable)) {
        processedParams = {
          productType: 'PRINT_PRODUCT',
          accessToken: config.fallbackAccessToken,
          siteName: "",
        }
        if (isProductAvailable) {
          processedParams.canvaProduct = canvaProduct
        }
      }

      // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
      // D-103824 - RT-ECVS-POD 1.0 changes are not visible in SDE after the Toggle is enabled
      let ssoSection = "";
      if(uploadFilePODData && uploadFilePODData.fromAdmin === undefined){
        ssoSection = customerData.get('sso_section')();
      }
      if (ssoSection !== null && typeof ssoSection !== 'undefined' && typeof processedParams !== 'undefined' && processedParams !== null) {
        processedParams.isSensitiveData = (window.checkout !== undefined && typeof window.checkout.can_show_sensative_message !== 'undefined') ? window.checkout.can_show_sensative_message : false;
      }

      if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin){
        let productId = (uploadFilePODData.id !== undefined) ? uploadFilePODData.id : '';
        let instanceId = (uploadFilePODData.instanceId !== undefined) ? uploadFilePODData.instanceId : '';
        let isCatalogMvpCloudDriveToggle = (uploadFilePODData.isCatalogMvpCloudDriveToggle !== undefined) ? uploadFilePODData.isCatalogMvpCloudDriveToggle : '';
        let isCloudDriveIntegrationBox = "1";
        let isCloudDriveIntegrationDropbox = "1";
        let isCloudDriveIntegrationGoogle = "1";
        let isCloudDriveIntegrationMicrosoft = "0";

        if (isCatalogMvpCloudDriveToggle && (isCatalogMvpCloudDriveToggle == true || isCatalogMvpCloudDriveToggle == 1)) {
          isCloudDriveIntegrationBox = (uploadFilePODData.isCloudDriveIntegrationBox !== undefined) ? uploadFilePODData.isCloudDriveIntegrationBox : 0;
          isCloudDriveIntegrationDropbox = (uploadFilePODData.isCloudDriveIntegrationDropbox !== undefined) ? uploadFilePODData.isCloudDriveIntegrationDropbox : 0;
          isCloudDriveIntegrationGoogle = (uploadFilePODData.isCloudDriveIntegrationGoogle !== undefined) ? uploadFilePODData.isCloudDriveIntegrationGoogle : 0;
          isCloudDriveIntegrationMicrosoft = (uploadFilePODData.isCloudDriveIntegrationMicrosoft !== undefined) ? uploadFilePODData.isCloudDriveIntegrationMicrosoft : 0;
        }

        processedParams = {
          "id": productId,
          "siteName": '',
          "accessToken": '',
          "productType": "PRINT_PRODUCT",
          "instanceId": instanceId,
          "pod_workflow_type": "MAGENTO_FLOW",
          "canvaProduct": {
            "is_editing": false,
            "instanceId": null,
            "productConfig": {
                "cartItemId": null
            }
          },
          "cloudDriveIntegrationFlag": {
              "enableCloudDrives": "1",
              "enableBox": isCloudDriveIntegrationBox,
              "enableDropbox": isCloudDriveIntegrationDropbox,
              "enableGoogleDrive": isCloudDriveIntegrationGoogle,
              "enableMicrosoftOneDrive": isCloudDriveIntegrationMicrosoft,
              "data_id": false
          },
          "isSensitiveData": false
        };
        let externalProd;
        if(window.e383157Toggle){
            externalProd = fxoStorage.get("adminconfig");
        }else{
            externalProd = JSON.parse(localStorage.getItem("adminconfig"));
        }
        let itemItanceId = 0;
        if(uploadFilePODData.fromCustomerAdmin !== undefined && uploadFilePODData.fromCustomerAdmin) {
          if (externalProd) {
            itemItanceId = externalProd.instanceId;
          }
        }
        else{
          if (externalProd) {
            let externalProdInstance = JSON.parse(externalProd.instance);
            itemItanceId = externalProdInstance.instanceId;
          }
        }
        queryParams = {
          "edit": itemItanceId
        }
      };
      return {
        ...queryParams,
        ...processedParams
      }
    }

    /**
     * Sets up a Promise to asynchronously wait for ProductEngine to return product's contentAssociation
     */
    function waitForContentAssociation() {
      const timeout = 100000; // 100 secs
      const startTime = Date.now();

      return new Promise(contentAssociationPromise);

      function contentAssociationPromise(resolve, reject) {
        if (window.serializedProductInstance) {
          resolve(window.serializedProductInstance);
        }
        else if (timeout && (Date.now() - startTime) >= timeout)
          reject(new Error("ProductEngine response timed out"));
        else
          setTimeout(contentAssociationPromise.bind(this, resolve, reject), 30);
      }
    }
  }
});
