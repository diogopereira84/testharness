define([
    "require",
    "exports",
    "peProductEngine"
], function (require, exports, ProductEngine) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    var ProductDetailsController = (function () {
        function ProductDetailsController(productDetailsUri) {
            this.productDetailsUri = null;
            this.productDetailsData = null;
            this.productDetailsTranslator = new ProductDetailsTranslator();
            this.productDetailsProvider = this.defaultProductDetailsProvider;
            this.productDetailsUri = productDetailsUri;
        }
        ProductDetailsController.prototype.buildDetailsPath = function (productDetailsId) {
            return this.productDetailsUri + '/productDetails-' + productDetailsId + '.json';
        };
        ProductDetailsController.prototype.defaultProductDetailsProvider = function (productDetailsId, callBack, errorCallback) {
            var _this = this;
            ProductEngine.Utils.httpGet(this.buildDetailsPath(productDetailsId), function (productDetailsData, status, headers, config) {
                if (productDetailsData) {
                    productDetailsData = _this.productDetailsTranslator.translateProductDetailsData(productDetailsData, productDetailsId);
                }
                if (callBack) {
                    callBack(productDetailsData);
                }
            }, function (productDetailsData, status, headers, config) {
                if (errorCallback) {
                    errorCallback(productDetailsData);
                }
            });
        };
        ProductDetailsController.prototype.fetchProductDetails = function (productDetailsId, callback, errorCallback) {
            if (!this.productDetailsProvider) {
                console.error('A product provider callback must be set before selecting any products');
            }
            else {
                this.productDetailsProvider(productDetailsId, function (productDetailsData) {
                    if (callback) {
                        callback(productDetailsData);
                    }
                }, function (data, status, headers) {
                    if (errorCallback) {
                        errorCallback(data, status, headers);
                    }
                });
            }
        };
        ProductDetailsController.prototype.selectProductDetails = function (productDetailsId, errorCallback) {
            var _this = this;
            this.fetchProductDetails(productDetailsId, function (productDetailsData) {
                _this.productDetailsData = productDetailsData;
            }, errorCallback);
        };
        return ProductDetailsController;
    }());
    exports.ProductDetailsController = ProductDetailsController;
    var ProductDetailsTranslator = (function () {
        function ProductDetailsTranslator() {
        }
        ProductDetailsTranslator.prototype.translateProductDetailsData = function (data, productDetailsId) {
            data = JSON.parse(data);
            if (data) {
                var productDetailsData = {
                    productDetails: data.productDetails,
                    descriptionGroups: this.translateGroups(data.productDetails.descriptionGroups),
                    images: this.translateGroups(data.productDetails.images)
                };
                return this.displayableProductDetails(productDetailsData, productDetailsId);
            }
        };
        ProductDetailsTranslator.prototype.displayableProductDetails = function (productDetailsData, productDetailsId) {
            return this.buildDisplayableProductDetailsItems(productDetailsData.productDetails, productDetailsData.images, productDetailsData.descriptionGroups);
        };
        ProductDetailsTranslator.prototype.translateGroups = function (groups) {
            var groupsList = new ProductEngine.ArrayList();
            if (groups) {
                for (var i = 0; i < groups.length; i++) {
                    groupsList.add(groups[i]);
                }
            }
            return groupsList;
        };
        ProductDetailsTranslator.prototype.buildDisplayableProductDetailsItems = function (details, images, descriptionGroups) {
            return {
                id: details.productDetailsId,
                productName: details.productName,
                productId: details.productId,
                productPresetId: details.productPresetId,
                summaryText: details.summaryText,
                pricingText: details.pricingText,
                keywords: details.keywords,
                descriptionGroups: descriptionGroups,
                images: images
            };
        };
        return ProductDetailsTranslator;
    }());
});
