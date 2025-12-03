define([
     'jquery',
     'mage/storage',
     'mage/url',
     'Fedex_ExpressCheckout/js/fcl-profile-session',
     'inBranchWarning',
     'Magento_Ui/js/modal/modal',
     'Fedex_Canva/js/model/canva',
     'Magento_Customer/js/customer-data',
     'fedex/storage',
     'Fedex_ProductEngine/js/product-controller',
     'Fedex_Delivery/js/model/toggles-and-settings',
     'domReady!'
], function($, storage, urlBuilder, profileSessionBuilder, inBranchWarning, modal, canvaModel, customerData, fxoStorage, productController, togglesAndSettings) {
   "use strict";
    var settingModalPopupOptions = null;
    var settingModalPopup = null;
    return async function (config) {
        let canArtWork,canvaPod,canvaProcess;
        if(window.e383157Toggle){
            canArtWork = fxoStorage.get('canva-artwork');
            canvaPod = fxoStorage.get('canva-pod');
            // code added to fix canva pod data
            if (canvaPod == null) {
                canvaPod = '{}';
            }
            canvaProcess = fxoStorage.get('canva-process');
        }else{
            canArtWork = JSON.parse(localStorage.getItem("canva-artwork"));
            canvaPod = localStorage.getItem("canva-pod");
            canvaProcess = localStorage.getItem("canva-process");
        }

        let FXOCMBaseUrl = config.FXOCMBaseUrl;
        let FXOCMClientId = config.FXOCMClientId;
        let sku = config.sku;
        let psku = '';
        let driveUploadEnable = config.driveUploadEnable;
        let boxEnabled = config.boxEnabled;
        let dropBoxEnable = config.dropBoxEnable;
        let googleEnabled = config.googleEnabled;
        let microSoftEnabled = config.microSoftEnabled;
        let configuratorTypeValue = config.configuratorType;
        let product = config.product;
        let instanceIdValue = config.instanceId;
        let getIsSdeStore = config.getIsSdeStore;
        let isMvpSharedCatalogEnable = config.isMvpSharedCatalogEnable;
        let uploadToQuoteData = config.uploadToQuoteData;
        let emptyUploadToQuoteConfigValue = config.emptyUploadToQuoteConfigValue;
        let additionalPrintInstructionsData = config.additionalPrintInstructionsData;
        let fedexAccountNumber = config.fedexAccountNumber;
        let enableUploadToQuote = config.enableUploadToQuote;
        let enableUploadToQuoteNSCFlow = config.enableUploadToQuoteNSCFlow;
        let loggedinCustomer = config.loggedinCustomer;
        let newDocumentsApiImage = config.newDocumentsApiImage;
        let singleProjectMode = config.singleProjectMode;
        let isSelfRegCustomerAdminUser = config.isSelfRegCustomerAdminUser;
        let isNonStandardCatalogToggleEnabled = config.isNonStandardCatalogToggleEnabled;
        let skuOnlyProductId = config.skuOnlyProductId;
        let redirectUrl = config.redirectUrl;
        let allProductsPageUrl = config.allProductsPageUrl;
        let viewProjectValue = config.viewProject;
        let siteName = config.siteName;
        let accessToken = config.accessToken;
        let configuratorStateId = config.configuratorStateId;
        let integrationType = config.integrationType;
        let footerContent = config.footerContent;
        let site = config.site;
        let footerText = config.footerText;
        let footerLink = config.footerLink;
        let cartCount = config.cartCount;
        let eproCustomDoc = config.eproCustomDoc;
        let fxoCMEnableForEproCustomDoc = config.fxoCMEnableForEproCustomDoc;
        let fixedQtyHandlerToggle = config.fixedQtyHandlerToggle;
        let convertToSizeModalText = config.convertToSizeModalText;
        let convertToSizeModalRedirectLink = config.convertToSizeModalRedirectLink;
        let pageGroups = config.pageGroups;
        let printReadyCustomDocFixToggle = config.printReadyCustomDocFixToggle;
        let isEproUploadToQuoteToggleEnable = config.isEproUploadToQuoteToggleEnable;
        let isEproCustomer = config.isEproCustomer;
        let cartUrl = urlBuilder.build('checkout/cart/');
        let locationUrl = 'https://local.fedex.com/en/fxo-only';
        let redirectLoginUrl = config.redirectLoginUrl;
        let poddata,canvaInstanceId,fedexAccountNumberStorage, exitLabel, uploadcancelurl, logourl;
        var fromCatalogPreview = false;
        if(config.fromCatalogPreview !== undefined && config.fromCatalogPreview == "true") {
            fromCatalogPreview = true;
        }

        if(window.e383157Toggle){
            poddata = fxoStorage.get("pod-data");
            canvaInstanceId = fxoStorage.get("canva-instanceId");
            fedexAccountNumberStorage = fxoStorage.get("selectedfedexAccount");
        } else {
            poddata = JSON.parse(localStorage.getItem("pod-data"));
            canvaInstanceId = localStorage.getItem("canva-instanceId");
            fedexAccountNumberStorage = localStorage.getItem("selectedfedexAccount");
        }
        let newProduct = config.newProduct;
        let newProductVariableValue = newProduct.replace(/\/+$/, '')
        let userWorkspace = config.userworkspace;
        let customizeConfigFields = config.customizeFields;
        if (fedexAccountNumber =='') {
            fedexAccountNumber = fedexAccountNumberStorage;
        }
        var rightFooterLinks = createFooterLinks(footerText, footerLink);

        uploadcancelurl = redirectUrl;
        logourl = window.location.origin+'/media/wysiwyg/fxo-logo.png';
        let vendorOptionsConfig = config.vendorOptionsConfig;
        let dyesubSKU = config.dyesubSKU;
        // Get cancel URL for URL REDIREC
        if (configuratorStateId && integrationType == "URL_REDIRECT") {
            if(window.e383157Toggle){
                uploadcancelurl = fxoStorage.get("uploadcancelurl");
                fxoStorage.delete('uploadcancelurl');
            } else {
                uploadcancelurl = window.localStorage.getItem("uploadcancelurl");
                window.localStorage.removeItem("uploadcancelurl");
            }
        }
        // For redirect flow
        let successUrl = '';
        // let exitRedirectUrl = window.location.origin;
        let exitRedirectUrl = config.redirectUrl;
        let isDyeSubFromCatalog = config.isDyeSubFromCatalog;
        let headerType = "DEFAULT";
        let footerType = "DEFAULT";
        let configuratorType = '';
        let instanceId = '';
        let newProductVariable = '';
        let viewProject = '';
        exitLabel = 'Back';

        configuratorType = configuratorTypeValue;
        instanceId = instanceIdValue;
        newProductVariable = newProductVariableValue;
        viewProject = viewProjectValue;

        if (integrationType == "URL_REDIRECT") {
             if (configuratorStateId == '') { // when open configurator first time.
                if(window.e383157Toggle){
                    fxoStorage.set("configuratorType",configuratorTypeValue);
                    configuratorType = fxoStorage.get("configuratorType");

                    fxoStorage.set("instanceId",instanceIdValue);
                    instanceId = fxoStorage.get("instanceId");

                    fxoStorage.set("newProductVariable",newProductVariableValue);
                    newProductVariable = fxoStorage.get("newProductVariable");

                    fxoStorage.set("viewProjectVariable",viewProjectValue);
                    viewProject = fxoStorage.get("viewProjectVariable");

                } else {
                    window.localStorage.setItem("configuratorType",configuratorTypeValue);
                    configuratorType = window.localStorage.getItem("configuratorType");

                    window.localStorage.setItem("instanceId",instanceIdValue);
                    instanceId = window.localStorage.getItem("instanceId");

                    window.localStorage.setItem("newProductVariable", newProductVariableValue);
                    newProductVariable = window.localStorage.getItem("newProductVariable");

                    window.localStorage.setItem("viewProjectVariable", viewProjectValue);
                    viewProject = window.localStorage.getItem("viewProjectVariable");
                }
            } else { // when redirect to main website after configuration.
                if(window.e383157Toggle){
                    configuratorType = fxoStorage.get("configuratorType");
                    instanceId = fxoStorage.get("instanceId");
                    newProductVariable = fxoStorage.get("newProductVariable");
                    viewProject = fxoStorage.get("viewProjectVariable");
                } else {
                    configuratorType = window.localStorage.getItem("configuratorType");
                    instanceId = window.localStorage.getItem("instanceId");
                    newProductVariable = window.localStorage.getItem("newProductVariable");
                    viewProject = window.localStorage.getItem("viewProjectVariable");
                }
            }

            // check configurator type for custom doc
            if (isMvpSharedCatalogEnable === "false" || (newProductVariable != "true" && !(typeof poddata !== 'undefined' && poddata.productPageToGoBack?.includes('isDyeSubFromCatalog=1'))))
            {
                successUrl = urlBuilder.build('configurator/index/index/responseid');
                if (configuratorType == "SETUP_CUSTOM_DOC" || (configuratorType == "EDIT" && isMvpSharedCatalogEnable === "true")) {
                    successUrl = urlBuilder.build('catalogmvp/configurator/index/responseid');
                    if (sku){
                        successUrl = urlBuilder.build('catalogmvp/configurator/index/sku/'+sku+'/responseid');
                    }
                }
            } else {
                successUrl = urlBuilder.build('catalogmvp/configurator/index/responseid');
            }


            if (!(typeof poddata !== 'undefined' && poddata.productPageToGoBack?.includes('isDyeSubFromCatalog=1'))) {

                if (document.referrer.includes("checkout/cart")) {
                    successUrl = urlBuilder.build('configurator/index/index/responseid');
                }
            }
        }
        if(configuratorType == 'VISUALIZE'){
            integrationType = "IFRAME";
        }

        if (integrationType == "URL_REDIRECT") {
            footerType = "CUSTOM";
            headerType = "CUSTOM";
        }
        settingModalPopupOptions = {
            type: 'popup',
            responsive: true,
            modalClass: 'shared-catalog-setting-modal',
            innerScroll: true,
            buttons: [],
            close: [],
        };
        // for catalogMVP, product data is already coming in product.
        if (configuratorType === 'EDIT' && isMvpSharedCatalogEnable === "false" && viewProject != 'true') {
            product = getItemToEdit(instanceId);
        }

        if (sku !== '') {
            psku = sku;
        } else if (poddata !== null && poddata !== undefined) {
            if (typeof poddata === 'object' && Object.keys(poddata).length > 0) {
                psku = poddata.id;
            }
        }

        if (configuratorType != 'CUSTOMIZE') {
            if (configuratorType != 'EDIT' && configuratorType != 'VISUALIZE') {
                if (poddata !== null && poddata !== undefined) {
                    if (typeof poddata === 'object' &&
                        Object.keys(poddata).length > 0) {
                        product = poddata.contentAssociation;
                    } else {
                        product = null;
                    }
                }
            } else {
                if (viewProject != 'true') {
                    psku = product.fxoMenuId
                }
            }
        }

        if (getIsSdeStore === "true") {
            singleProjectMode = true;
        }

        let useNewDocumentApiValue = false;
        let allowFileUpload = true;

        let isAllowFileUploadCatalogFlow = false;
        if (typeof window !== "undefined" && typeof window.checkout !== "undefined") {
            isAllowFileUploadCatalogFlow =
            window.checkout.allow_file_upload_catalog_flow || false;
        }

        if (isMvpSharedCatalogEnable === "true" && newProductVariable === "true") {
            useNewDocumentApiValue = true;
            singleProjectMode = true;
            if(isAllowFileUploadCatalogFlow) {
                allowFileUpload = true;
            } else {
                allowFileUpload = false;
            }
        }

        let isFixAllowFileUploadIssue = false;
        if (typeof window !== "undefined" && typeof window.checkout !== "undefined") {
            isFixAllowFileUploadIssue =
            window.checkout.fix_allow_file_upload_issue || false;
        }

        let isRemoveNewProjectCta = window?.checkout?.remove_new_project_cta;

        if (isFixAllowFileUploadIssue) {
            // Send new document API true when edit from shared catalog
            if (isMvpSharedCatalogEnable === "true" && sku != '' && configuratorType == "EDIT") {
                useNewDocumentApiValue = true;
                allowFileUpload = true;
                if (isRemoveNewProjectCta) {
                    singleProjectMode = true;
                }
            }
        } else {
            // Send new document API true when edit from shared catalog
            if (isMvpSharedCatalogEnable === "true" && sku != '' && configuratorType == "EDIT") {
                useNewDocumentApiValue = true;
                allowFileUpload = false;
            }
        }

        if(configuratorType == 'CUSTOMIZE') {
            allowFileUpload = false;
        }

        if(configuratorType == "VISUALIZE" && config.useNewDocumentApi !== undefined && config.useNewDocumentApi == "true") {
            useNewDocumentApiValue = true;
        }
        //if New Documents Api Image Preview Toggle is On then set useNewDocumentApiValue true
        if(newDocumentsApiImage=="true"){
            useNewDocumentApiValue = true;
        }

        let extendedFileRetenion = false;
        if (loggedinCustomer === 'true' && newProductVariable != "true") {
            extendedFileRetenion = true;
        }

        let hideFileExpirationText = false;
        //Do not show hideFileExpirationText for non-login User
        if(loggedinCustomer === 'false'){
           hideFileExpirationText = true;
        }

        //Catalog MVP creating new product and click cancel button, Page reload and open configurator in edit flow
        let configuratorJson;
        if(window.e383157Toggle){
            configuratorJson = fxoStorage.get("configurator_json")
        }else{
            configuratorJson = window.localStorage.getItem("configurator_json")
        }
        if (configuratorJson && configuratorType == 'NEW' && newProductVariable === "true") {
            product = window.e383157Toggle ? configuratorJson : JSON.parse(configuratorJson);
            configuratorType = 'EDIT';
        }

        //product need to undefined for view my projects page, if product data has '{}'(cheked length for this) and not null
        if(product !=null && Object.keys(product).length === 0) {
            product = undefined;
        }

        if(printReadyCustomDocFixToggle) {
            if (configuratorType == 'CUSTOMIZE' && typeof product !== 'undefined'
                && typeof product.contentAssociations !== 'undefined' && Array.isArray(product.contentAssociations)
                && product.contentAssociations.length > 0) {
                product.contentAssociations.forEach(function(value, index, content) {
                    if ((typeof content[index].pageGroups === 'undefined' || content[index].pageGroups.length === 0)
                        && typeof pageGroups[index].contentReference !== 'undefined' && content[index].contentReference == pageGroups[index].contentReference) {
                        content[index].pageGroups = pageGroups[index].pageGroups;
                    }
                });
            }
        }

        //Set documentRetentionPeriod
        let documentRetentionPeriodUnits='';
        let documentRetentionPeriodValue='';
        if (loggedinCustomer === 'true'){
                documentRetentionPeriodUnits = 'DAYS';
                documentRetentionPeriodValue = 30;
            if (getIsSdeStore === "true") {
                documentRetentionPeriodUnits = 'DAYS';
                documentRetentionPeriodValue = 2;
            }
        }else{
            documentRetentionPeriodUnits = 'HOURS';
            documentRetentionPeriodValue = 24;
        }

        var containerEl = document.querySelector('#iframe_container');
        if (fromCatalogPreview) {
            containerEl = document.querySelector('#iframe_container_preview_catalog');
        }
        let sdkConfig = {
            baseUrl: FXOCMBaseUrl,
            client_id: FXOCMClientId,
            containerEl: containerEl,
            heartbeatCallback: function(response) {},
            integrationType: integrationType,
            loadedCallback: function(response){},
            replaceWithLocal: false,
            successCallback: function(response) {
                if(response.errors && response.errors . length > 0) {
                    console.log(JSON.stringify(response.errors));
                    if(response.errors[0].code=='USER_CANCELED_UPLOAD' || response.errors[0].code=='USER_CANCELED_WORKFLOW'){
                        window.parent.location.href = uploadcancelurl;
                    }
                } else {
                    if (response.loggedInUser !== undefined && response.loggedInUser==true && integrationType=='URL_REDIRECT') {
                           addProjectWorkSpace(response, true, redirectLoginUrl);
                           return;
                    }
                    if(response.allProductsPageUrl){
                        addProjectWorkSpace(response);
                        return;
                    }
                    // Code added to fetch allowed quantity product
                    let productEngineUrl = typeof window.checkout.mix_cart_product_engine_url === 'string'
                        ? window.checkout.mix_cart_product_engine_url
                        : '';
                    let productControllerObj = new productController.ProductController(productEngineUrl);
                    let productInstanceData = JSON.stringify(response.product);
                    let version = JSON.stringify(response.product.version);
                    let responseProductId = JSON.stringify(response.product.id);
                    let ControllId = 2;
                    console.log("ControllId=>"+ControllId);
                    let isQuantityAvailable = '';
                    if (version == 0) {
                        isQuantityAvailable =
                            new Promise(function(resolve, reject) {
                            resolve(true);
                        });
                    } else {
                        isQuantityAvailable =
                            new Promise(function(resolve, reject) {
                                productControllerObj.selectProductById(responseProductId, version, ControllId, (responseData) => {
                                    productControllerObj.loadSerializedProduct(productInstanceData);
                                    let displayableProduct = productControllerObj.displayableProduct.properties;
                                    let allowedValueNames = getAllowedValuesByName(displayableProduct);
                                    console.log("allowedValueNames=>"+allowedValueNames);
                                    if (allowedValueNames) {
                                        if (fixedQtyHandlerToggle === "true") {
                                            if (isMvpSharedCatalogEnable === "true" && (newProductVariable === "true" || (typeof poddata !== 'undefined' && poddata.productPageToGoBack?.includes('isDyeSubFromCatalog=1')))) {
                                                // for new catalogmvp product
                                                response.product.quantityChoices = allowedValueNames;
                                            } else if (isMvpSharedCatalogEnable === "true" && sku != '' && configuratorType == "EDIT") {
                                                // for editing catalogmvp product
                                                response.product.quantityChoices = allowedValueNames;
                                            } else {
                                                response.fxoProductInstance = { quantityChoices: allowedValueNames};
                                            }
                                        } else {
                                            response.fxoProductInstance = { quantityChoices: allowedValueNames};
                                        }
                                    }
                                    resolve(true);

                                }, () => { console.log("Failure: Unable to select product."); resolve(true)});
                        });
                    }
                    isQuantityAvailable.then(function() {
                        if(configuratorType == "SETUP_CUSTOM_DOC" && isMvpSharedCatalogEnable === "true") {
                         setPOD2EditableCustomerAdmin(response, configuratorType, integrationType);
                        } else if (configuratorType == 'CUSTOMIZE') {
                         ajaxCallForAddtoCart(response, false);
                        } else if (isMvpSharedCatalogEnable === "true" && (instanceId == 123 || newProductVariable === "true" || typeof poddata !== 'undefined' && poddata.productPageToGoBack?.includes('isDyeSubFromCatalog=1'))) {
                         setPOD2EditableCustomerAdmin(response, configuratorType, integrationType);
                        }  else if (configuratorType == 'VISUALIZE' && config.addToCartCTAText == "ADD TO CART") {
                            ajaxCallForAddtoCart(response, false);
                        } else if (configuratorType == 'VISUALIZE' && config.addToCartCTAText == "CUSTOMIZE") {
                            window.parent.location.href = redirectUrl;
                        } else if (configuratorType == 'VISUALIZE') {
                            window.parent.location.href = redirectUrl;
                        } else {
                         if (configuratorType == 'EDIT') {
                             setPOD2EditableCustomerAdmin(response, "EDIT", integrationType);
                             response.instanceId = instanceId;
                         }
                         let isExpressCheckout = false;
                         if (typeof(response.expressCheckoutButtonSelected) !== "undefined" && response.expressCheckoutButtonSelected !== null) {
                             isExpressCheckout = response.expressCheckoutButtonSelected;
                         }

                         if(config.isPreviewVISUALIZE != "true" && !_.isEmpty(canArtWork)){
                             response.product.designId = canArtWork.designId;
                             response.product.partnerProductId = canArtWork.partnerProductId;
                             if(canvaProcess=="EDIT" && canvaInstanceId !=''){
                                 response.instanceId = canvaInstanceId;
                             }
                         }
                         // below code will be enable in future
                         // if(singleProjectMode=='false'){
                         //     addProjectWorkSpace(response);
                         // }
                         ajaxCallForAddtoCart(response, isExpressCheckout);
                        }
                    });
                }
            }
        };


        let  integrationDetails = {};
        if (isMvpSharedCatalogEnable === "true" && (window.location.href.indexOf('configurator/index/index') === -1 || isDyeSubFromCatalog === "1")) {
            integrationDetails =  {
                "integratorId": "POD2.0",
                "integrationType": integrationType,
                "workflow": "CATALOG"
            };
        } else {
            // Common values for all stores
            integrationDetails =  {
                "integratorId": "POD2.0",
                "integrationType": integrationType
            };
        }

        let allowQuantityChange = true;
        if (isMvpSharedCatalogEnable === "true" && (instanceId == 123 || newProductVariable === "true" || isDyeSubFromCatalog === "1")) {
            allowQuantityChange = false;
        }

        let documentUploadConfigurations = {
            'cloudDriveFlags': {
                'enableCloudDrives':driveUploadEnable,
                'enableBox':boxEnabled,
                'enableDropbox':dropBoxEnable,
                'enableGoogleDrive':googleEnabled,
                'enableMicrosoftOneDrive':microSoftEnabled
            },
            'extendFileRetention':extendedFileRetenion,
            'hideFileExpirationText':hideFileExpirationText,
            'documentRetentionPeriod':{
                'units': documentRetentionPeriodUnits,
                'value': documentRetentionPeriodValue
            },
        };

        let heartbeat = {
            'enabled':false,
            'frequencyInMinutes':5
        };

        let addToCartCTA = addtoCartCTAValue(configuratorType, isMvpSharedCatalogEnable, newProductVariable, instanceId, viewProject,isDyeSubFromCatalog);

        let ConfiguratorSession = '';

        //Canva Code
        //set canva object blank when get psku
        if (psku!='') {
            canArtWork= '';
        }
        let canvaSku = '';
        if(config.isPreviewVISUALIZE != "true" && !_.isEmpty(canArtWork)){
            canvaSku = '1534436209752-2';
            if(canvaPod != '{}'){
              canvaSku = canvaPod.id;
            }
            psku = null;
        }
        if(canvaProcess=="EDIT"){
            addToCartCTA = "Save Changes";
        }
        if (configuratorType == "VISUALIZE" && config.addToCartCTAText !== undefined) {
            addToCartCTA = config.addToCartCTAText;
            if(config.addToCartCTAText == "ADD TO CART") {
                psku = config.sku;
            }
        }
        if(configuratorType == 'SETUP_CUSTOM_DOC') {
            addToCartCTA = "COMPLETE";
            config.addToCartCTAText = "COMPLETE";
        }

        // for epro custom docs
        if (eproCustomDoc == 1 && fxoCMEnableForEproCustomDoc == 1) {
            site = siteName;
            siteName = '';
        }

        // Start B-1899708|D-177741 : RT-ECVS- Customer Admin - Ability to enable user instruction only for customer Admin.
        let uploadToQuote = '';
        let ctcAdminFlow = false;
        if (isNonStandardCatalogToggleEnabled && isMvpSharedCatalogEnable === "true" && isSelfRegCustomerAdminUser
         && newProductVariable === "true"
        ) {
            enableUploadToQuote = enableUploadToQuoteNSCFlow;
            uploadToQuote = additionalPrintInstructionsData;
            skuOnlyProductId = skuOnlyProductId;
        } else if (!isNonStandardCatalogToggleEnabled && useNewDocumentApiValue && isMvpSharedCatalogEnable === "true") {
            uploadToQuote = emptyUploadToQuoteConfigValue;
            enableUploadToQuote = false;
            site = '';
            siteName = '';
            skuOnlyProductId = '';
        } else {
            uploadToQuote = uploadToQuoteData;
        }
        // end B-1899708
        // B-2180907 : Disable additional print instruction when upload to quote disabled in company in epro
        if (isEproUploadToQuoteToggleEnable && isEproCustomer && enableUploadToQuote == 'false') {
            uploadToQuote = emptyUploadToQuoteConfigValue;
            enableUploadToQuote = false;
            site = '';
            siteName = '';
        }

        if (ctcAdminFlow !== false || configuratorType != 'NEW' || integrationDetails.workflow === "CATALOG") {
            convertToSizeModalText = '';
            convertToSizeModalRedirectLink = '';
        }

        let params = new URLSearchParams(window.location.search);
        if (togglesAndSettings.isToggleEnabled('tiger_e468338') && params.has('isBundle')) {
            uploadToQuote = emptyUploadToQuoteConfigValue;
            enableUploadToQuote = false;
            site = '';
            siteName = '';
            singleProjectMode = true;
        }

        //End Canva Code
        if (config.isPreviewVISUALIZE != "true" && !_.isEmpty(canArtWork) && (psku == null || psku.length === 0) && viewProject != 'true') {
                    ConfiguratorSession = {
                        integrationDetails,
                        'configuratorOptions': {
                            'addToCartCTAText' : addToCartCTA,
                            'allowFileUpload':true,
                            'allowProjectNameChange':true,
                            'allowQuantityChange':true,
                            'skuOnlyProductID':skuOnlyProductId,
                            'documents':[{}],
                            documentUploadConfigurations,
                            'configurationType': configuratorType,  // for new product NEW for edit EDIT
                            'enableUserProvidedInstructions':enableUploadToQuote,
                            'expressCheckoutButtonEnabled': expressCheckoutToggle(config),
                            'footerOptions': {
                                'type':footerType,
                                'backgroundColor': "#4d148c",
                                'text': footerContent,
                                'textColor': "#ffffff",
                                'rightFooterLinks': rightFooterLinks
                           },
                            'headerOptions': {
                                'type':headerType,
                                'backgroundColor': "#4d148c",
                                'logoURL':logourl,
                                'showLogo': true,
                                'cartCount':cartCount,
                                'locationsURL':locationUrl,
                                'cartURL':cartUrl
                           },
                            heartbeat,
                            'navigationOptions': {
                                'successRedirectUrl': successUrl,
                                'exitRedirectUrl': exitRedirectUrl,
                                'exitRedirectUrlLabel': exitLabel
                            },
                            'priceDisplaySettings': {
                                'showDiscountDetails':true,
                                'showPrice':true,
                                'showPriceDetails':true
                            },
                            product,
                            uploadToQuote,
                            'primaryCTA': {},
                            'sensitiveDataFlow':getIsSdeStore,
                            'singleProjectMode':true,
                             userWorkspace,
                            'userPreferences':{
                                'fedExAccountNumber': fedexAccountNumber,
                                'accessToken': accessToken,
                                'siteName': site, // company name
                                'site': null, // legacy site name
                                'ctcAdminFlow': ctcAdminFlow
                            },
                            'integratorProductReference':canvaSku,
                            'useNewDocumentApi': useNewDocumentApiValue,
                            'canvaProduct': {
                                'artworkId': canArtWork.artworkId,
                                'artworkTitle': canArtWork.artworkTitle,
                                'designId': canArtWork.designId,
                                'pageCount': canArtWork.pageCount,
                                'partnerProductId': canArtWork.partnerProductId,
                                'previewImages': canArtWork.previewImages,
                                'previewImageSrc': canArtWork.previewImageSrc
                           },
                           'webAnalyticsOptions': {
                            'disableContentSquare': config.fxo_web_analytics_scripts_contentsquare_contentsquare_active,
                            'disableAdobeAnalytics': config.fxo_web_analytics_scripts_gdl_gdl_active,
                            'disableAppDynamics': config.fxo_web_analytics_scripts_app_dynamics_active,
                            'disableForsta': config.fxo_web_analytics_scripts_forsta_enabled
                        }
                        },
                        'codeChallenge': ' '
                };
        } else {

            ConfiguratorSession = {
                    integrationDetails,
                    'configuratorOptions': {
                        'addToCartCTAText' : addToCartCTA,
                        'allowFileUpload':allowFileUpload,
                        'allowProjectNameChange':true,
                        'allowQuantityChange':allowQuantityChange,
                        'skuOnlyProductID':skuOnlyProductId,
                        'documents':[{}],
                        documentUploadConfigurations,
                        'configurationType': configuratorType,  // for new product NEW for edit EDIT
                        'convertToSizeModalText': convertToSizeModalText,
                        'convertToSizeModalTextLink': convertToSizeModalRedirectLink,
                        'enableUserProvidedInstructions':enableUploadToQuote,
                        'expressCheckoutButtonEnabled': expressCheckoutToggle(config),
                        'footerOptions': {
                            'type':footerType,
                            'backgroundColor': "#4d148c",
                            'text': footerContent,
                            'textColor': "#ffffff",
                            'rightFooterLinks': rightFooterLinks
                        },
                        'headerOptions': {
                            'type':headerType,
                            'backgroundColor': "#4d148c",
                            'logoURL':logourl,
                            'showLogo': true,
                            'cartCount':cartCount,
                            'locationsURL':locationUrl,
                            'cartURL':cartUrl
                       },
                        heartbeat,
                        'navigationOptions': {
                            'allProductsPageUrl': allProductsPageUrl,
                            'successRedirectUrl': successUrl,
                            'exitRedirectUrl': exitRedirectUrl,
                            'exitRedirectUrlLabel': exitLabel
                       },
                        'priceDisplaySettings': {
                            'showDiscountDetails':true,
                            'showPrice':true,
                            'showPriceDetails':true
                        },
                        'primaryCTA': {},
                        'productSelector': {
                            'productId': psku // for edit case, get product id from JSON. for NEW it will be SKU
                        },
                        product,
                        uploadToQuote,
                        'sensitiveDataFlow':getIsSdeStore,
                        'singleProjectMode':singleProjectMode,
                        userWorkspace,
                        'userPreferences':{
                            'fedExAccountNumber': fedexAccountNumber,
                            'accessToken': accessToken,
                            'siteName': site, // company name
                            'site': null, // legacy site name
                            'ctcAdminFlow': ctcAdminFlow
                        },
                        'useNewDocumentApi': useNewDocumentApiValue,
                        'integratorProductReference':psku,
                        'webAnalyticsOptions': {
                            'disableContentSquare': config.fxo_web_analytics_scripts_contentsquare_contentsquare_active,
                            'disableAdobeAnalytics': config.fxo_web_analytics_scripts_gdl_gdl_active,
                            'disableAppDynamics': config.fxo_web_analytics_scripts_app_dynamics_active,
                            'disableForsta': config.fxo_web_analytics_scripts_forsta_enabled
                        }
                    },
                    'codeChallenge': ' '
                };

            if(window.tiger_E_478196_dye_sub_pod_2_updates && poddata?.contentAssociation?.vendorTemplate
                && ConfiguratorSession.configuratorOptions.configurationType !== undefined && ConfiguratorSession.configuratorOptions.configurationType !== "EDIT") {
                ConfiguratorSession.configuratorOptions.configurationType = "CUSTOMERS_CANVAS_" + ConfiguratorSession.configuratorOptions.configurationType.toUpperCase();
                ConfiguratorSession.configuratorOptions.vendorOptions = poddata.contentAssociation.vendorTemplate;
            }else if (window.tiger_E_478196_dye_sub_pod_2_updates && ConfiguratorSession.configuratorOptions.configurationType === "EDIT" && config.isDyeSubProduct === "1") {
                let vendorOptions = {};

                if (product?.productConfig?.vendorOptions) {
                    vendorOptions = product.productConfig.vendorOptions;
                } else if(Object.keys(vendorOptionsConfig).length !== 0){
                    vendorOptions = vendorOptionsConfig;
                }
                else if (product?.vendorOptions) {
                    vendorOptions =
                        product.vendorOptions && Object.keys(product.vendorOptions).length > 0
                            ? product.vendorOptions[0]
                            : {};
                } else {
                    const productInstance = getProductInstance(instanceId);
                    vendorOptions = productInstance?.productConfig?.vendorOptions;
                }
                if (vendorOptions !==undefined && (Object.keys(vendorOptions).length > 0)) {
                    ConfiguratorSession.configuratorOptions.configurationType = "CUSTOMERS_CANVAS_" + ConfiguratorSession.configuratorOptions.configurationType.toUpperCase();
                    ConfiguratorSession.configuratorOptions.vendorOptions = vendorOptions;
                   const storeFrontUserId = vendorOptions.userId;

                    try {
                        // Making the AJAX request to get the userToken
                        const response = await $.ajax({
                            url: urlBuilder.build("customercanvas/index/index"),
                            type: "GET",
                            dataType: "json",
                            data: { storeFrontUserId }
                        });

                        // If the response is successful and contains a userToken
                        if (response.success && response.data) {
                            ConfiguratorSession.configuratorOptions.vendorOptions.userToken = response.data;
                        } else {
                            console.warn("Error fetching token:", response.message || "Unknown error");
                        }
                    } catch (error) {
                        console.error("Error fetching token:", error);
                    }
                } else {
                    console.warn("No vendorOptions found in the product instance.");
                }
            }


        }
        if(configuratorType == 'VISUALIZE' && config.isPreviewVISUALIZE !== undefined && config.isPreviewVISUALIZE == "true") {
            let catalogDescription = null;
            let catalogTags = null;
            if (config.catalogDescription) {
                catalogDescription = config.catalogDescription;
            }
            if (config.catalogTags) {
                catalogTags = config.catalogTags;
            }
            ConfiguratorSession.configuratorOptions.catalogProductDetails = {
                'catalogDescription' : catalogDescription,
                'catalogTags': catalogTags
            }
        }
        if (configuratorType == 'EDIT' && ConfiguratorSession !== undefined && ConfiguratorSession.configuratorOptions !== undefined) {
            if (customizeConfigFields && ConfiguratorSession.configuratorOptions.documents !== undefined) {
                ConfiguratorSession.configuratorOptions.documents = customizeConfigFields;
            }
        }

        if (configuratorType == 'CUSTOMIZE' && ConfiguratorSession !== undefined && ConfiguratorSession.configuratorOptions !== undefined) {
            if (ConfiguratorSession.configuratorOptions.addToCartCTAText !== undefined) {
                ConfiguratorSession.configuratorOptions.addToCartCTAText = 'Add To Cart';
            }

            if (customizeConfigFields && ConfiguratorSession.configuratorOptions.documents !== undefined) {
                ConfiguratorSession.configuratorOptions.documents = customizeConfigFields;
            }
            if (ConfiguratorSession.configuratorOptions.product !== undefined) {
                ConfiguratorSession.configuratorOptions.product = product;
            }
            if (ConfiguratorSession.configuratorOptions.useNewDocumentApi !== undefined) {
                ConfiguratorSession.configuratorOptions.useNewDocumentApi = true;
            }

            if (eproCustomDoc == 1 && fxoCMEnableForEproCustomDoc == 1 && !customizeConfigFields) {
                ConfiguratorSession.configuratorOptions.useNewDocumentApi = false;
            }
        }

        if (configuratorStateId && integrationType == "URL_REDIRECT") {
            (window).FedExOfficePCSdk.getProductConfiguratorResult(sdkConfig, configuratorStateId);
        } else {
            (window).FedExOfficePCSdk.openProductConfigurator(sdkConfig, ConfiguratorSession);
        }
        if (configuratorType == 'SETUP_CUSTOM_DOC' && ConfiguratorSession !== undefined && ConfiguratorSession.configuratorOptions !== undefined) {
            ConfiguratorSession.configuratorOptions.addToCartCTAText = 'COMPLETE';
        }
        $(".shared-catalog-setting-form-container .complete-setting").on("click", function(event) {
            if (($('body').hasClass('catalog_mvp_custom_docs')) && ($(this).hasClass('setupcustomize'))) {
                let customizeField = $('#customization_fields').val();

                let documentData = JSON.parse(customizeField);
                let externalProduct = JSON.parse($('#shared-catalog-setting-externalproduct').val());

                if (documentData && ConfiguratorSession !== undefined && ConfiguratorSession.configuratorOptions !== undefined) {
                    ConfiguratorSession.configuratorOptions.documents = documentData;

                    if (ConfiguratorSession.configuratorOptions.configurationType !== undefined) {
                        ConfiguratorSession.configuratorOptions.configurationType = 'SETUP_CUSTOM_DOC';
                        ConfiguratorSession.configuratorOptions.addToCartCTAText = 'COMPLETE';
                        configuratorType = 'SETUP_CUSTOM_DOC';
                        if (integrationType == "URL_REDIRECT") {
                            configuratorTypeValue = 'SETUP_CUSTOM_DOC';
                            if(window.e383157Toggle){
                                fxoStorage.set("configuratorType",configuratorTypeValue);
                                fxoStorage.set("userProductname",$("#shared-catalog-setting-name").val());
                                fxoStorage.set("userProductDesc",$("#shared-catalog-setting-description").val());
                                fxoStorage.set("userProductStartDate",$("#start-date").val());
                                fxoStorage.set("userProductEndDate",$("#shared-catalog-setting-start-date").val());
                                if ($("#no-end-date").is(':checked')){
                                    fxoStorage.set("userProductNoEndDate","true");
                                } else {
                                    fxoStorage.set("userProductNoEndDate","false");
                                }
                                fxoStorage.set("userProductCustomizeToggle",$("#customizable-toggle").val());
                                var chips = jQuery('.tags-wrapper').children();
                                var tags = [];
                                for(let i = 0; i < chips.length; i++) {
                                    if(chips[i].classList.contains('chips'))
                                    {
                                        tags.push(chips[i].getElementsByClassName("tag-label")[0].innerHTML);
                                    }
                                }
                                fxoStorage.set("userProductTags",tags.join());
                                fxoStorage.set("userProductTag",$("#shared-catalog-setting-tag").val());
                            } else {
                                window.localStorage.setItem("configuratorType",configuratorTypeValue);
                                window.localStorage.setItem("userProductname",$("#shared-catalog-setting-name").val());
                                window.localStorage.setItem("userProductDesc",$("#shared-catalog-setting-description").val());
                                window.localStorage.setItem("userProductStartDate",$("#start-date").val());
                                window.localStorage.setItem("userProductEndDate",$("#shared-catalog-setting-start-date").val());
                                if ($("#no-end-date").is(':checked')){
                                    window.localStorage.setItem("userProductNoEndDate","true");
                                } else {
                                    window.localStorage.setItem("userProductNoEndDate","false");
                                }
                                window.localStorage.setItem("userProductCustomizeToggle",$("#customizable-toggle").val());
                                var chips = jQuery('.tags-wrapper').children();
                                var tags = [];
                                for(let i = 0; i < chips.length; i++) {
                                    if(chips[i].classList.contains('chips'))
                                    {
                                        tags.push(chips[i].getElementsByClassName("tag-label")[0].innerHTML);
                                    }
                                }
                                window.localStorage.setItem("userProductTags",tags);
                                window.localStorage.setItem("userProductTag",$("#shared-catalog-setting-tag").val());
                            }
                        }
                    }

                    if (externalProduct) {
                        ConfiguratorSession.configuratorOptions.product = externalProduct;
                    }

                    $('#iframe_container iframe').remove();
                    (window).FedExOfficePCSdk.openProductConfigurator(sdkConfig, ConfiguratorSession);
                     if (integrationType == "IFRAME") {
                        settingModalPopup.modal('closeModal');
                    }
                }
            }
        });
    }

    function addToCartRedirect(isExpressCheckout) {
        clearStorageValue();
        if (isExpressCheckout) {
            profileSessionBuilder.setRemoveExpressStorage();
            window.location.href = urlBuilder.build('checkout');
        } else {
            if(window.e383157Toggle){
                fxoStorage.delete('express-checkout');
            }else{
                localStorage.removeItem('express-checkout');
            }
            window.location.href = urlBuilder.build('checkout/cart/');
        }
    }

    function addtoCartCTAValue (configuratorType, isMvpSharedCatalogEnable, newProductVariable, instanceId, viewProject, isDyeSubFromCatalog) {
        let addToCartCTA = "";

        if (configuratorType == 'EDIT' && viewProject != 'true') {
            addToCartCTA = "Save Changes";
        }
        if (isMvpSharedCatalogEnable === "true" && (instanceId == 123 || newProductVariable === "true" || isDyeSubFromCatalog === "1")) {
            addToCartCTA = "Continue";
        }
        if (configuratorType == 'CUSTOMIZE') {
            addToCartCTA = 'Add To Cart';
        }
        if(configuratorType == "SETUP_CUSTOM_DOC") {
            addToCartCTA == "COMPLETE";
        }
        return addToCartCTA;
    }

    function ajaxCallForAddtoCart(response, isExpressCheckout) {
        let itemId = "";
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.get('edit') !== undefined) {
            itemId = urlParams.get('edit');
        }
        // Remove POD data local storage after add to cart.
        if(window.e383157Toggle){
            fxoStorage.delete('pod-data');
        }else{
            localStorage.removeItem('pod-data');
        }
        $.ajax({
            url: urlBuilder.build('cart/product/add'),
            type: 'POST',
            showLoader: true,
            data: { data: JSON.stringify(response) , itemId: itemId},
            dataType: 'json',
            success: function(data) {
                if(data.isInBranchProductExist)
                    {
                        inBranchWarning.inBranchWarningPopup();
                    }else{
                    addToCartRedirect(isExpressCheckout);
                    }
            },
            error: function(data) {
                if(data.isInBranchProductExist)
                    {
                        inBranchWarning.inBranchWarningPopup();
                    }else{
                        addToCartRedirect(isExpressCheckout);
                    }
            }
        });
    }

    /*
    Add response userWorkspace data into session and if login customer then add into Database also.
    */
    function addProjectWorkSpace(response, redirectLogin = 0, redirectLoginUrl='') {
        $.ajax({
            url: urlBuilder.build('fxocm/batchupload/index'),
            type: 'POST',
            data: { data: JSON.stringify(response) },
            dataType: 'json',
            success: function(data) {
                if(redirectLogin){
                    window.location.href = redirectLoginUrl;
                    return;
                }
                if(response.allProductsPageUrl){
                    window.location.href = response.allProductsPageUrl;
                }
            },
            error: function(data) {
            }
        });
    }

    function setPOD2EditableCustomerAdmin(fxoProduct, configuratorType = "", integrationType) {
        if(window.e383157Toggle){
            fxoStorage.set("configurator_json",fxoProduct);
            if(fxoProduct.customDocumentDetails !== undefined && Object.keys(fxoProduct.customDocumentDetails).length !== 0) {
                fxoStorage.set("configurator_customizedata",JSON.stringify(fxoProduct.customDocumentDetails));
            }
        }else{
            window.localStorage.setItem("configurator_json",JSON.stringify(fxoProduct.product));
            if(fxoProduct.customDocumentDetails !== undefined && Object.keys(fxoProduct.customDocumentDetails).length !== 0) {
                window.localStorage.setItem("configurator_customizedata",JSON.stringify(fxoProduct.customDocumentDetails));
            }
        }

        if (configuratorType == "SETUP_CUSTOM_DOC") {
            $("#shared-catalog-setting-externalproduct").val(JSON.stringify(fxoProduct.product));
            if($('body').hasClass('catalog_mvp_custom_docs') && fxoProduct.customDocumentDetails !== undefined) {
                $("#customization_fields").val(JSON.stringify(fxoProduct.customDocumentDetails));
            }

            if (integrationType == "URL_REDIRECT") {
                if(window.e383157Toggle){
                    let productName = fxoStorage.get("userProductname");
                    let userProductDesc = fxoStorage.get("userProductDesc");
                    let userProductStartDate = fxoStorage.get("userProductStartDate");
                    let userProductEndDate = fxoStorage.get("userProductEndDate");
                    let userProductNoEndDate = fxoStorage.get("userProductNoEndDate");
                    let userProductCustomizeToggle = fxoStorage.get("userProductCustomizeToggle");
                    let userProductTags = fxoStorage.get("userProductTags");
                    let userProductTag = fxoStorage.get("userProductTag");
                    if (productName) {
                        $("#shared-catalog-setting-name").val(productName);
                    }
                    if (userProductDesc) {
                        $("#shared-catalog-setting-description").val(userProductDesc);
                    }
                    if (userProductStartDate) {
                        $("#start-date").val(userProductStartDate);
                    }
                    if (userProductNoEndDate == "false") {
                        $("#no-end-date").prop("checked", false);
                    } else {
                        $("#no-end-date").prop("checked", true);
                    }
                    if (userProductCustomizeToggle) {
                        $("#customizable-toggle").val(userProductCustomizeToggle);
                    }
                    if (userProductTags) {
                        let valuesArray = userProductTags.split(',').map(value => value.trim());
                        valuesArray.forEach((value) => {
                            $('<div class="chips"><div class="tag-button"><div class="tag-label">'+value+'</div></div></div>').insertBefore("#shared-catalog-setting-tag");
                        });
                    }
                    if (userProductTag) {
                        $("#shared-catalog-setting-tag").val(userProductTag);
                    }
                    if (userProductEndDate) {
                        $("#shared-catalog-setting-start-date").val(userProductEndDate);
                    }

                    $("#shared-catalog-setting-price").val(fxoStorage.get("product-price"));
                } else {
                    let productName = window.localStorage.getItem("userProductname");
                    let userProductDesc = window.localStorage.getItem("userProductDesc");
                    let userProductStartDate = window.localStorage.getItem("userProductStartDate");
                    let userProductEndDate = window.localStorage.getItem("userProductEndDate");
                    let userProductNoEndDate = window.localStorage.getItem("userProductNoEndDate");
                    let userProductCustomizeToggle = window.localStorage.getItem("userProductCustomizeToggle");
                    let userProductTags = window.localStorage.getItem("userProductTags");
                    let userProductTag = window.localStorage.getItem("userProductTag");
                    if (productName) {
                        $("#shared-catalog-setting-name").val(productName);
                    }
                    if (userProductDesc) {
                        $("#shared-catalog-setting-description").val(userProductDesc);
                    }
                    if (userProductStartDate) {
                        $("#start-date").val(userProductStartDate);
                    }
                    if (userProductNoEndDate == "false") {
                        $("#no-end-date").prop("checked", false);
                    } else {
                        $("#no-end-date").prop("checked", true);
                    }
                    if (userProductCustomizeToggle) {
                        $("#customizable-toggle").val(userProductCustomizeToggle);
                    }
                    if (userProductTags) {
                        let valuesArray = userProductTags.split(',').map(value => value.trim());
                        valuesArray.forEach((value) => {
                            $('<div class="chips"><div class="tag-button"><div class="tag-label">'+value+'</div></div></div>').insertBefore("#shared-catalog-setting-tag");
                        });
                    }
                    if (userProductTag) {
                        $("#shared-catalog-setting-tag").val(userProductTag);
                    }
                    if (userProductEndDate) {
                        $("#shared-catalog-setting-start-date").val(userProductEndDate);
                    }

                    $("#shared-catalog-setting-price").val(window.localStorage.getItem("product-price"));
                }

                if(fxoProduct.integratorProductReference != undefined) {
                $("#catalogmvp_fxo_menu_id").val(fxoProduct.integratorProductReference);
                }
                $("#shared-catalog-setting-externalproduct").val(JSON.stringify(fxoProduct.product));
                if(window.tiger_E_478196_dye_sub_pod_2_updates){
                    $("#catalogmvp_vendor_options").val(JSON.stringify(fxoProduct.vendorOptions));
                }


                if($('body').hasClass('catalog_mvp_custom_docs') && fxoProduct.customDocumentDetails !== undefined) {
                    $("#customization_fields").val(JSON.stringify(fxoProduct.customDocumentDetails));
                }
            }

            $(".complete-setting").removeClass("setupcustomize");
            $(".complete-setting").trigger("click");
        } else {
            if ($('body').hasClass('catalog_mvp_custom_docs') && fxoProduct.customDocumentDetails !== undefined) {
                let customizeDocument = false;
                if (configuratorType == "EDIT" && Object.keys(fxoProduct.customDocumentDetails).length === 0) {
                    if(window.e383157Toggle){
                        if (fxoStorage.get("configurator_customizedata")) {
                            fxoProduct.customDocumentDetails = fxoStorage.get("configurator_customizedata");
                        }
                    }else{
                        if (window.localStorage.getItem("configurator_customizedata")) {
                            fxoProduct.customDocumentDetails = JSON.parse(window.localStorage.getItem("configurator_customizedata"));
                        }
                    }
                }
                if (fxoProduct.customDocumentDetails !== undefined) {
                    let arr = fxoProduct.customDocumentDetails;

                    $.each(arr,function(key,value){
                        if (value.documentId !== undefined && value.formFields !== undefined) {
                            customizeDocument = true;
                            return false;
                        }
                    });
                }
                if (customizeDocument) {
                    $('.shared-catalog-setting-form-container .setting-customize-container').show();
                    $('.shared-catalog-setting-form-container .actions').removeClass('non_customize_action');
                    if(!$('.shared-catalog-setting-form-container .setting-customize-container #customizable-toggle').is(":checked")) {
                        $('.shared-catalog-setting-form-container .setting-customize-container #customizable-toggle').trigger("click").prop('checked', true);
                    }
                }
            }

            if ($('body').hasClass('catalog_mvp_custom_docs')) {
                settingModalPopup = $('#setting-formp-popup').modal(settingModalPopupOptions, $('#setting-formp-popup'));
                settingModalPopup.modal('openModal');
            } else {
                let options = {
                    type: 'popup',
                    responsive: true,
                    modalClass: 'shared-catalog-setting-modal',
                    innerScroll: true,
                    buttons: [],
                    close: [],
                };
                $('#setting-formp-popup').modal(options, $('#setting-formp-popup')).modal('openModal');
            }
            if (fxoProduct.product.userProductName != undefined ) {
                let userProductname = fxoProduct.product.userProductName;
              $("#shared-catalog-setting-name").val(userProductname);
            }
            let productPrice = getPriceFromRateCall(JSON.stringify(fxoProduct.product));

            if(fxoProduct.integratorProductReference != undefined) {
              $("#catalogmvp_fxo_menu_id").val(fxoProduct.integratorProductReference);
            }
            $("#shared-catalog-setting-externalproduct").val(JSON.stringify(fxoProduct.product));
            if(window.tiger_E_478196_dye_sub_pod_2_updates) {
                $("#catalogmvp_vendor_options").val(JSON.stringify(fxoProduct.vendorOptions));
            }

            if($('body').hasClass('catalog_mvp_custom_docs') && fxoProduct.customDocumentDetails !== undefined) {
                if(window.tiger_d_217190!== undefined  && window.tiger_d_217190 && (JSON.stringify(fxoProduct.customDocumentDetails) !== JSON.stringify([]))){
                    $("#customization_fields").val(JSON.stringify(fxoProduct.customDocumentDetails));
                }
            }
        }

    }

    function expressCheckoutToggle(config) {
      let isFclCustomer = typeof (window.checkout.is_fcl_customer) !== "undefined" && window.checkout.is_fcl_customer !== null ? window.checkout.is_fcl_customer : false;
      let isCommercial = typeof (window.checkout.is_commercial) !== "undefined" && window.checkout.is_commercial !== null ? window.checkout.is_commercial : false;
      let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';
      let match = document.cookie.match(new RegExp('(^| )'+fclCookieConfigVal+'=([^;]+)'));
      let params = new URLSearchParams(window.location.search);
      if (togglesAndSettings.isToggleEnabled('tiger_e468338') && (params.has('isBundle') || config.isBundleProductSetupCompleted === "false")) {
          return false; // Disable express checkout for bundle products
      }

      if (!isFclCustomer && match && match[2].toLowerCase() != 'no' && !isCommercial) {
        isFclCustomer = true;
      }

      if(isFclCustomer && !isCommercial && window.checkout.is_quote_price_is_dashable) {
        return false;
      }

      return isFclCustomer;
    }

    function getPriceFromRateCall(productJson) {
        var url = urlBuilder.build('fxocm/rate/index');
        jQuery.ajax({
            type: "post",
            url: url,
            showLoader: true,
            data: { data: productJson },
            success: function(data) {
                if (data){
                    if(window.e383157Toggle){
                        fxoStorage.set("product-price",data);
                    } else {
                        window.localStorage.setItem("product-price",data);
                    }
                    $("#shared-catalog-setting-price").val(data);
                }
            }
        });
    }

    function getItemToEdit(instanceId) {
        let cart, cache, productInstance;
        if (window.e383157Toggle) {
            cart = customerData.get('cart')();
            productInstance = cart.items.find((item) => {
                if (item.product_attributesetname === 'FXOPrintProducts' && !!item.externalProductInstance) {
                    return typeof item.externalProductInstance === 'object' ?
                        item.externalProductInstance.instanceId.toString() === instanceId.toString() :
                        JSON.parse(item.externalProductInstance).instanceId.toString() === instanceId.toString();
                }
            });

            if (togglesAndSettings.isToggleEnabled('tiger_e468338') && !productInstance) {
                cart.items.forEach((item) => {
                    if (item.product_attributesetname === 'FXOPrintProducts' && item.product_type === "bundle") {
                        if (item.childrenExternalProductInstance && typeof item.childrenExternalProductInstance === 'object') {
                            Object.values(item.childrenExternalProductInstance).forEach(child => {
                                let childObj = child;
                                if (typeof child !== 'object') {
                                    try {
                                        childObj = JSON.parse(child);
                                    } catch (e) {
                                        childObj = null;
                                    }
                                }
                                if (
                                    childObj &&
                                    childObj.instanceId &&
                                    childObj.instanceId.toString() === instanceId.toString()
                                ) {
                                    productInstance = childObj;
                                }
                            });
                        }
                    }
                });
            }

        } else {
            cache = JSON.parse(window.localStorage.getItem('mage-cache-storage'));
            productInstance = cache.cart.items.find((item) => {
                if (item.product_attributesetname === 'FXOPrintProducts' && !!item.externalProductInstance) {
                    return typeof item.externalProductInstance === 'object' ?
                        item.externalProductInstance.instanceId.toString() === instanceId.toString() :
                        JSON.parse(item.externalProductInstance).instanceId.toString() === instanceId.toString();
                }
            });
        }
        if (productInstance) {
            if(!(productInstance.externalProductInstance === undefined)){
                productInstance = typeof productInstance.externalProductInstance === 'object' ?
                    productInstance.externalProductInstance :
                    JSON.parse(productInstance.externalProductInstance);
            }
        }
        return productInstance;
    }

    // Function to find allowedValues when name is "Product Quantity Sets"
    function getAllowedValuesByName(data) {
        for (var i = 0; i < data.length; i++) {
            if (data[i].key === "PRODUCT_QTY_SET") {
                var allowedValues = data[i].allowedValues;
                var valuesArray = [];
                for (var j = 0; j < allowedValues.length; j++) {
                    valuesArray.push(allowedValues[j].value);
                }
                return valuesArray;
            }
        }
        return null; // If not found
    }

    function clearStorageValue(){
        if (window.e383157Toggle) {
            fxoStorage.delete('configuratorType');
            fxoStorage.delete('instanceId');
            fxoStorage.delete('newProductVariable');
            fxoStorage.delete('viewProjectVariable');
        } else {
            window.localStorage.removeItem("configuratorType");
            window.localStorage.removeItem("instanceId");
            window.localStorage.removeItem("newProductVariable");
            window.localStorage.removeItem("viewProjectVariable");
        }
    }

   function createFooterLinks(textString, linkString) {
            if(textString === undefined || linkString === undefined) {
                return;
            }
            var textArray = textString.split(',').map(function(item) {
                return item.trim();
            });
            var linkArray = linkString.split(',').map(function(item) {
                return item.trim();
            });

            var output = [];
            for (var i = 0; i < textArray.length; i++) {
                if(textArray[i] && linkArray[i]){
                    if(!(linkArray[i].includes("www.fedex.com"))){
                       linkArray[i] =  urlBuilder.build(linkArray[i]);
                    }
                  var linkObj = {
                    'text': textArray[i],
                    'link': linkArray[i]
                  };
                   output.push(linkObj);
                }
            }
            return output;
        }

    /**
     *
     * @param instanceId
     * @returns {*}
     */
    function getProductInstance(instanceId) {
        // Parse the cache stored in localStorage
        const cache = JSON.parse(window.localStorage.getItem('mage-cache-storage'));
        let productInstance = null;

        // If cache exists and cart items are present
        if (cache && cache.cart && Array.isArray(cache.cart.items)) {
            // Find the product instance
            productInstance = cache.cart.items.find((item) => {
                if (item.product_attributesetname === 'FXOPrintProducts' && !!item.externalProductInstance) {
                    const externalInstance = item.externalProductInstance;
                    const instanceIdStr = instanceId.toString();

                    // Handle cases where externalProductInstance is an object or a JSON string
                    if (typeof externalInstance === 'object') {
                            return externalInstance.instanceId.toString() === instanceIdStr;
                    } else {
                        const parsedInstance = JSON.parse(externalInstance);
                        return parsedInstance.instanceId.toString() === instanceIdStr;
                    }
                }
            });
        }

        // If a product instance is found, ensure externalProductInstance is properly parsed
        if (productInstance && productInstance.externalProductInstance !== undefined) {
            productInstance = typeof productInstance.externalProductInstance === 'object' ?
                productInstance.externalProductInstance :
                JSON.parse(productInstance.externalProductInstance);
        }

        return productInstance;
    }

});