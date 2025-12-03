var defineObj;
if (window.location.href.indexOf("catalog/product") > -1) {
  defineObj = ["jquery", "", "loader", "support","fedex/storage"];
} else {
  defineObj = ["jquery", "Magento_Customer/js/customer-data", "loader", "support","fedex/storage"];
}
define(defineObj, function (
    $,
    customerData,
    loader,
    support,
    fxoStorage
) {
    "use strict";

    /**
     * @author Daniel Dominguez
     * DO NOT EDIT SDK.
     */
    (function () {
        var Envs;
        (function (Envs) {
            Envs["LOCAL"] = "LOCAL";
            Envs["DEV"] = "DEV";
            Envs["PERFORMANCE"] = "PERFORMANCE";
            Envs["TEST"] = "TEST";
            Envs["PROD"] = "PROD";
        })(Envs || (Envs = {}));
        var ProductType;
        (function (productType) {
            productType["COMMERCIAL_PRODUCT"] = "COMMERCIAL_PRODUCT";
            productType["PRINT_PRODUCT"] = "PRINT_PRODUCT";
        })(ProductType || (ProductType = {}));
        var MessageType;
        (function (messageType) {
            messageType["BACK_PDP"] = "pod-to-magento";
            messageType["CONFIG"] = "fedex-configure";
            messageType["MAGENTO_TO_POD"] = "magento-to-pod";
            messageType["EDIT"] = "fedex-edit";
            messageType["POD_MAGENTO_READY"] = "pod-magento-ready";
            messageType["MAGENTO_TO_POD_CANVA"] = "magento-to-pod-canva";
            messageType["CUSTOMIZE"] = "fedex-custom-doc";
        })(MessageType || (MessageType = {}));
        /**
         * FedEx Iframe API
         */
        var FedExIframeAPI = /*#__PURE__*/ (function () {
            function FedExIframeAPI(
                iframeContainerEl,
                _id,
                _env,
                successCallback,
                errorCallback,
                productType,
                accessToken,
                siteName
            ) {
                var _this = this;
                _classCallCheck(this, FedExIframeAPI);
                /**
                 * @param callback - function that is passed a configured product.
                 * Creates iframe and appends to container the element provided.
                 * Listens for FedEx specific message event and passes data to callback provided.
                 * @param redirectPdpCallback
                 */
                _defineProperty(
                    this,
                    "onConfigureProduct",
                    function (callback, redirectPdpCallback) {
                        var magentoPODData = _this.magentoPODData;
                        var contentAssociation =
                            magentoPODData.contentAssociation;
                        var iframeEl = _this._buildIframe({
                            setting: "config",
                            env: _this.env,
                            id: _this.id,
                            dataToPass: contentAssociation,
                            callback: callback,
                            redirectPdpCallback: redirectPdpCallback,
                        });
                        _this.iframeContainerEl.appendChild(iframeEl);
                    }
                );
                /**
                 * Handles iframe for editing a configured product.
                 * @param fxoProduct - object containing fxoMenuId and fxoProductInstance.
                 * @param callback - function to receive updated product.
                 */
                _defineProperty(
                    this,
                    "onEditConfiguredProduct",
                    function (fxoProduct, callback) {
                        _this._setProductMenuId(fxoProduct.fxoMenuId);
                        _this._setInstanceId(fxoProduct.instanceId);
                        var iframeEl = _this._buildIframe({
                            setting: "edit",
                            env: _this.env,
                            dataToPass: !fxoProduct.fxoProductInstance
                                ? fxoProduct
                                : fxoProduct.fxoProductInstance,
                            callback: callback,
                        });
                        _this.iframeContainerEl.appendChild(iframeEl);
                        _this._captureEventData(callback);
                    }
                );
                /**
                 * Public method
                 * Sets data for PoD initialization from Magento iframe.
                 * @param data
                 */
                _defineProperty(this, "setMagentoPODData", function (data) {
                    _this.magentoPODData = data;
                });
                /**
                 * Sets menu id in edit work flow.
                 * @param menuId
                 */
                _defineProperty(this, "_setProductMenuId", function (menuId) {
                    _this.id = menuId;
                });
                /**
                 * Sets product instance id used for editing
                 */
                _defineProperty(this, "_setInstanceId", function (instanceId) {
                    _this.instanceId = instanceId;
                });
                /**
                 * Sets event listenter on window.
                 * Removes event listenter once function is called.
                 * @param callback - passed data from event.data;
                 */
                _defineProperty(this, "_captureEventData", function (callback) {
                    window.addEventListener("message", function (e) {
                        if (
                            e.data.type === MessageType.CONFIG ||
                            e.data.type === MessageType.CUSTOMIZE ||
                            e.data.type === MessageType.BACK_PDP
                        ) {
                            var fxoProduct = {
                                fxoMenuId: _this.id,
                                fxoProductInstance: e.data.product,
                                productType: _this.productType,
                                instanceId: _this.instanceId,
                            };
                            callback(fxoProduct);
                        }
                    });
                });
                /**
                 * Creates and configures iframe element.
                 * @param IframeConfig -> {
                 * setting - ('config' or 'edit') used to determine url for iframe.
                 * id - if setting === 'config' set id in url.
                 * env - baseURL environment type.
                 * dataToPass - if in edit mode, pass product instance back to configurator for editing.
                 * }
                 * @returns - configured iframe element.
                 */
                _defineProperty(this, "_buildIframe", function (_ref) {
                    var setting = _ref.setting,
                        id = _ref.id,
                        env = _ref.env,
                        dataToPass = _ref.dataToPass,
                        callback = _ref.callback,
                        redirectPdpCallback = _ref.redirectPdpCallback;
                    var magentoPODData = _this.magentoPODData;
                    var contentAssociation = magentoPODData.contentAssociation;
                    var canvaProduct = magentoPODData.canvaProduct;
                    var iframeEl = document.createElement("iframe");

                    magentoPODData.showDiscountBreakdown = true;
                    let uploadFileStoragePODData;
                    if(window.e383157Toggle){
                        uploadFileStoragePODData = fxoStorage.get("pod-data");
                    }else{
                        uploadFileStoragePODData = JSON.parse(localStorage.getItem("pod-data"));
                    }
                    let isAdminProductCreation = uploadFileStoragePODData && uploadFileStoragePODData.fromAdmin !== undefined && uploadFileStoragePODData.fromAdmin;

                    let isUploadToQuoteEnable = (!uploadFileStoragePODData && typeof (window.checkout.xmen_upload_to_quote) !== "undefined" && window.checkout.xmen_upload_to_quote !== null) ? window.checkout.xmen_upload_to_quote : false;
                    let uploadToQuoteConfigValues = (!uploadFileStoragePODData && typeof (window.checkout.upload_to_quote_config_values) !== "undefined" && window.checkout.upload_to_quote_config_values !== null) ? window.checkout.upload_to_quote_config_values : false;

                    if (isUploadToQuoteEnable) {
                        magentoPODData.uploadToQuote = JSON.parse(uploadToQuoteConfigValues);
                    }

                    var cloudDriveIntegrationFlag = 0;
                    if (
                        typeof magentoPODData.cloudDriveIntegrationFlag !==
                        "undefined" &&
                        magentoPODData.cloudDriveIntegrationFlag !== null
                    ) {
                        cloudDriveIntegrationFlag = {};
                        Object.keys(
                            magentoPODData.cloudDriveIntegrationFlag
                        ).forEach(function (key) {
                            cloudDriveIntegrationFlag[key] =
                                magentoPODData.cloudDriveIntegrationFlag[
                                    key
                                    ] === "1";
                        });
                    }
                    if (setting === "config") {
                        window.addEventListener("message", function (e) {
                            var fxoProduct = {
                                fxoMenuId: _this.id,
                                fxoProductInstance: e.data.product,
                                productType: _this.productType,
                                instanceId: _this.instanceId,
                            };
                            if (e.data.type === MessageType.BACK_PDP) {
                                redirectPdpCallback(fxoProduct);
                            } else if (
                                e.data.type === MessageType.CONFIG ||
                                e.data.type === MessageType.CUSTOMIZE
                            ) {
                                callback(fxoProduct);
                            } else if (
                                e.data.type === MessageType.POD_MAGENTO_READY
                            ) {
                                if (
                                    canvaProduct &&
                                    !_.isEmpty(canvaProduct.designId)
                                ) {
                                    let uploadFilePODData;
                                    if(window.e383157Toggle){
                                        uploadFilePODData = fxoStorage.get("pod-data");
                                    }else{
                                        uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
                                    }
                                    if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin) {
                                        iframeEl.contentWindow.postMessage(
                                        {
                                            type: MessageType.MAGENTO_TO_POD,
                                            data: {
                                            data: contentAssociation,
                                            cloudDriveFlags: cloudDriveIntegrationFlag,
                                            hideQuantity: true,
                                            actionButtonText: "Continue",
                                            useNewDocumentApi: true,
                                            fedexAccount: uploadFilePODData.fedexAccount ?? "",
                                            showDiscountBreakdown: true
                                            }
                                        },
                                        "*"
                                        );
                                    }
                                    else {
                                        iframeEl.contentWindow.postMessage(
                                            {
                                                type: MessageType.MAGENTO_TO_POD_CANVA,
                                                data: {
                                                    designInfo: canvaProduct,
                                                    isExpressCheckout:
                                                    magentoPODData.isExpressCheckout,
                                                    fedexAccount:
                                                    magentoPODData.fedexAccount,
                                                },
                                            },
                                            "*"
                                        );
                                    }
                                } else {
                                    let uploadFilePODData;
                                    if(window.e383157Toggle){
                                        uploadFilePODData = fxoStorage.get("pod-data");
                                    }else{
                                        uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
                                    }
                                    if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin) {

                                        iframeEl.contentWindow.postMessage(
                                        {
                                            type: MessageType.MAGENTO_TO_POD,
                                            data: {
                                            data: contentAssociation,
                                            cloudDriveFlags: cloudDriveIntegrationFlag,
                                            hideQuantity: true,
                                            actionButtonText: "Continue",
                                            useNewDocumentApi: true,
                                            fedexAccount: uploadFilePODData.fedexAccount ?? "",
                                            showDiscountBreakdown: true
                                            }
                                        },
                                        "*"
                                        );
                                    }
                                    else {
                                        iframeEl.contentWindow.postMessage(
                                            {
                                                type: MessageType.MAGENTO_TO_POD,
                                                data: {
                                                    data: contentAssociation,
                                                    cloudDriveFlags:
                                                    cloudDriveIntegrationFlag,
                                                    showDiscountBreakdown:
                                                    magentoPODData.showDiscountBreakdown,
                                                    fedexAccount:
                                                    magentoPODData.fedexAccount,
                                                    uploadToQuote: magentoPODData.uploadToQuote,
                                                },
                                            },
                                            "*"
                                        );
                                    }
                                }
                                let uploadFilePODData;
                                if(window.e383157Toggle){
                                    uploadFilePODData = fxoStorage.get("pod-data");
                                }else{
                                    uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
                                }
                                if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin) {
                                    $(".loading-mask").hide();
                                } else {
                                    $("body").loader("hide");
                                }
                            }
                        });
                    }
                    iframeEl.src = _this._setIframeSrc(setting, env, id);
                    iframeEl.id = "fedex_iframe";
                    iframeEl.ariaLabel = "Configurator iFrame";
                    iframeEl.onerror = function () {
                        return _this.errorCallback
                            ? _this.errorCallback()
                            : null;
                    };
                    // event occurs when the object has been loaded.
                    iframeEl.onload = function () {
                        if (_this.successCallback) {
                            _this.successCallback();
                        }
                        if (dataToPass && setting === "edit") {
                            let uploadFilePODData;
                            if(window.e383157Toggle){
                                uploadFilePODData = fxoStorage.get("pod-data");
                            }else{
                                uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
                            }
                            if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin) {
                                iframeEl.contentWindow.postMessage(
                                    {
                                    type: MessageType.EDIT,
                                    data: {
                                        data: dataToPass,
                                        cloudDriveFlags: cloudDriveIntegrationFlag,
                                        hideQuantity: true,
                                        actionButtonText: "Continue",
                                        useNewDocumentApi: true,
                                        fedexAccount: uploadFilePODData.fedexAccount ?? "",
                                        showDiscountBreakdown: true
                                    },
                                    },
                                    "*"
                                );
                            } else {
                                iframeEl.contentWindow.postMessage(
                                    {
                                        type: MessageType.EDIT,
                                        data: {
                                            showDiscountBreakdown:magentoPODData.showDiscountBreakdown,
                                            fedexAccount: magentoPODData.fedexAccount,
                                            uploadToQuote: magentoPODData.uploadToQuote,
                                            data: dataToPass,
                                            cloudDriveFlags:
                                            cloudDriveIntegrationFlag,
                                        },
                                    },
                                    "*"
                                );
                            }
                        }
                        let uploadFilePODData;
                        if(window.e383157Toggle){
                            uploadFilePODData = fxoStorage.get("pod-data");
                        }else{
                            uploadFilePODData = JSON.parse(localStorage.getItem("pod-data"));
                        }
                        if(uploadFilePODData && uploadFilePODData.fromAdmin !== undefined && uploadFilePODData.fromAdmin) {
                            $(".loading-mask").hide();
                        } else {
                            $("body").loader("hide");
                        }

                    };
                    iframeEl.style.width = "100%";
                    // D-105045 : RT-ECVS-Double scroll bar should not be there in file upload and configurator screens
                    if (
                        magentoPODData !== null &&
                        typeof magentoPODData !== "undefined" &&
                        typeof magentoPODData.isSensitiveData !== "undefined" &&
                        magentoPODData.isSensitiveData === true
                    ) {
                        iframeEl.scrolling = "auto";
                        iframeEl.frameborder = "0";
                        iframeEl.style.height = "100rem";
                    } else {
                        iframeEl.style.height = "100vh";
                    }
                    iframeEl.style.border = "none";
                    iframeEl.style.display = "inherit";
                    return iframeEl;
                });
                /**
                 * Determines environemnt url to be set as iframe src.
                 * @param env - enum value determining environment type.
                 * @returns - baseURL.
                 */
                _defineProperty(this, "_handleEnvBaseURL", function (env) {
                    // commenting this section for backup purpose
                    /*var path = "apps/ondemand";
                    switch (env) {
                        case Envs.LOCAL:
                            return "https://localhost.fedex.com:4200";
                        case Envs.DEV:
                            return "https://wwwbase.idev.fedex.com/" + path;
                        case Envs.PERFORMANCE:
                            return "https://wwwdrt.idev.fedex.com/" + path;
                        case Envs.TEST:
                            return "https://wwwtest.fedex.com/" + path;
                        case Envs.PROD:
                            return "https://www.fedex.com/" + path;
                        default:
                            return "";
                    }*/
                    return "";
                });
                this.iframeContainerEl = iframeContainerEl;
                this.id = _id;
                this.env = _env;
                this.successCallback = successCallback;
                this.errorCallback = errorCallback;
                this.productType = productType;
                this.accessToken = accessToken || '';
                this.siteName = siteName || "";
                this.instanceId = null;
                this.magentoPODData;
            }
            _createClass(FedExIframeAPI, [
                {
                    key: "_setIframeSrc",
                    value:
                        /**
                         * Handles URL configuration.
                         * @param setting - determine the src of the iframe.
                         * @param env - enum value determining environment type.
                         * @param id - if setting === 'config', set id.
                         * @returns - configured iframe src url.
                         */
                        function _setIframeSrc(setting, env, id) {

                            var podData = this.magentoPODData;
                            var canvaProduct = podData.canvaProduct;
                            var baseURL = "";
                            if(window.configuratorUrl !== undefined){
                                baseURL = window.configuratorUrl;
                            }
                           // var baseURL = this._handleEnvBaseURL(env); //'https://wwwbase.idev.fedex.com/apps/ondemand';//'http://localhost:4200/iframe';//this._handleEnvBaseURL(env);

                            if (
                                canvaProduct &&
                                !_.isEmpty(canvaProduct.designId)
                            ) {
                                return (
                                    baseURL +
                                    "/iframe/templates/canva-configurator"
                                );
                            }
                            switch (this.productType) {
                                case ProductType.PRINT_PRODUCT: {
                                    if (podData.id || podData.edit) {
                                        switch (setting) {
                                            case "config":
                                                // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
                                                return (
                                                    baseURL +
                                                    "/iframe/print-products/"
                                                        .concat(
                                                            id,
                                                            "/file-workspace?site="
                                                        )
                                                        .concat(
                                                            podData.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            podData.accessToken,
                                                            "&pod_workflow_type=MAGENTO_FLOW&sensitive_data_workflow="
                                                        )
                                                        .concat(
                                                            podData.isSensitiveData,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            case "edit":
                                                // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
                                                return (
                                                    baseURL +
                                                    "/iframe/print-products/iframe-configure-edit?site="
                                                        .concat(
                                                            podData.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            podData.accessToken,
                                                            "&pod_workflow_type=MAGENTO_FLOW&sensitive_data_workflow="
                                                        )
                                                        .concat(
                                                            podData.isSensitiveData,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            default:
                                                return "";
                                        }
                                    } else {
                                        switch (setting) {
                                            case "config":
                                                // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
                                                return (
                                                    baseURL +
                                                    "/iframe/print-products/"
                                                        .concat(
                                                            id,
                                                            "/file-workspace?site="
                                                        )
                                                        .concat(
                                                            this.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            this.accessToken,
                                                            "&pod_workflow_type=MAGENTO_FLOW&sensitive_data_workflow="
                                                        )
                                                        .concat(
                                                            podData.isSensitiveData,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            case "edit":
                                                // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
                                                return (
                                                    baseURL +
                                                    "/iframe/print-products/iframe-configure-edit?site="
                                                        .concat(
                                                            this.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            this.accessToken,
                                                            "&pod_workflow_type=MAGENTO_FLOW&sensitive_data_workflow="
                                                        )
                                                        .concat(
                                                            podData.isSensitiveData,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            default:
                                                return "";
                                        }
                                    }
                                }
                                case ProductType.COMMERCIAL_PRODUCT: {
                                    if (podData.id || podData.edit) {
                                        switch (setting) {
                                            case "config":
                                                return (
                                                    baseURL +
                                                    "/iframe/custom-product/custom-doc-landing-page?catalogId="
                                                        .concat(
                                                            podData.id,
                                                            "&site="
                                                        )
                                                        .concat(
                                                            podData.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            podData.accessToken,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            case "edit":
                                                return (
                                                    baseURL +
                                                    "/iframe/custom-product/custom-doc-landing-page?catalogId="
                                                        .concat(
                                                            podData.id,
                                                            "&site="
                                                        )
                                                        .concat(
                                                            podData.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            podData.accessToken,
                                                            "&iframeEdit=true&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            default:
                                                return "";
                                        }
                                    } else {
                                        switch (setting) {
                                            case "config":
                                                return (
                                                    baseURL +
                                                    "/iframe/custom-product/custom-doc-landing-page?catalogId="
                                                        .concat(
                                                            this.id,
                                                            "&site="
                                                        )
                                                        .concat(
                                                            this.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            this.accessToken,
                                                            "&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            case "edit":
                                                return (
                                                    baseURL +
                                                    "/iframe/custom-product/custom-doc-landing-page?catalogId="
                                                        .concat(
                                                            this.id,
                                                            "&site="
                                                        )
                                                        .concat(
                                                            this.siteName,
                                                            "&access_token="
                                                        )
                                                        .concat(
                                                            this.accessToken,
                                                            "&iframeEdit=true&express_checkout="
                                                        )
                                                        .concat(
                                                            podData.isExpressCheckout
                                                        )
                                                );
                                            default:
                                                return "";
                                        }
                                    }
                                }
                            }
                        },
                },
            ]);
            return FedExIframeAPI;
        })();
        /**
         * FedEx SDK for file-workspace and configurator iframe.
         */
        var FedExSDK = /*#__PURE__*/ _createClass(function FedExSDK() {
            _classCallCheck(this, FedExSDK);
            /**
             * @param initializationConfig - FedEx Iframe SDK configuration object containing id and container element.
             * @param successCallback - called when iframe is loaded successfully, passing data of type any back to consuming app.
             * @param errorCallback - called when error occurs passing error to consuming application.
             * @returns - Promise containing new instance of FedExAPI or InitError.
             */
            _defineProperty(
                this,
                "initialize",
                function (
                    initializationConfig,
                    successCallback,
                    errorCallback
                ) {
                    document.domain = "fedex.com";
                    return new Promise(function (resolve, reject) {
                        if (
                            initializationConfig &&
                            initializationConfig.id &&
                            initializationConfig.containerEl &&
                            initializationConfig.env &&
                            initializationConfig.productType
                        ) {
                            var containerEl = initializationConfig.containerEl,
                                id = initializationConfig.id,
                                env = initializationConfig.env,
                                productType = initializationConfig.productType,
                                accessToken = initializationConfig.accessToken,
                                siteName = initializationConfig.siteName;
                            resolve(
                                new FedExIframeAPI(
                                    containerEl,
                                    id,
                                    env,
                                    successCallback,
                                    errorCallback,
                                    productType,
                                    accessToken,
                                    siteName
                                )
                            );
                        } else {
                            var error = {
                                error: "Error initializing SDK.",
                            };
                            reject(error);
                        }
                    });
                }
            );
        });
        window.FedExSDK = new FedExSDK();
    })();
});
