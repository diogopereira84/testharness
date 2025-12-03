define([
    "require",
    "exports",
    "peProductMenuController",
    "peProductEngine"
], function (require, exports, menuDataURI, ProductEngine) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    var ProductionController = (function () {
        function ProductionController(menuDataURI) {
            this.menuData = null;
            this.menuDataURI = menuDataURI;
        }
        ProductionController.prototype.getProductionGroups = function (productInstances, successCallback, errorCallback) {
            var _this = this;
            var productionGroups = [];
            var resultGroups = [];
            this.defaultMenuDataProvider(function (productMenuData) {
                if (productMenuData.productMenuDetails) {
                    for (var i = 0; i < productInstances.length; i++) {
                        var productId = productInstances[i].id.toString();
                        for (var j = 0; j < productMenuData.productMenuDetails.length; j++) {
                            if (productMenuData.productMenuDetails[j].productId === productId) {
                                productionGroups = productMenuData.productMenuDetails[j].productionGroups;
                                if (productionGroups.length > 1) {
                                    if (productInstances[i].isOutSourced === true) {
                                        resultGroups = _this.insertData('NAVITOR', productInstances[i], resultGroups);
                                    }
                                    else {
                                        resultGroups = _this.insertData('FXO', productInstances[i], resultGroups);
                                    }
                                }
                                else {
                                    resultGroups = _this.insertData(productionGroups[0], productInstances[i], resultGroups);
                                }
                                break;
                            }
                        }
                    }
                }
                if (successCallback) {
                    successCallback(resultGroups);
                }
            }, errorCallback);
        };
        ProductionController.prototype.insertData = function (inGroup, inProduct, resultGroups) {
            var group = {
                productionGroup: null,
                products: null
            };
            if (resultGroups.length === 0) {
                group.productionGroup = inGroup;
                var inProducts = [];
                inProducts.push(inProduct);
                group.products = inProducts;
                resultGroups.push(group);
            }
            else {
                var found = false;
                for (var i = 0; i < resultGroups.length; i++) {
                    if (resultGroups[i].productionGroup === inGroup) {
                        resultGroups[i].products.push(inProduct);
                        found = true;
                    }
                }
                if (found === false) {
                    group.productionGroup = inGroup;
                    var inProducts = [];
                    inProducts.push(inProduct);
                    group.products = inProducts;
                    resultGroups.push(group);
                }
            }
            return resultGroups;
        };
        ProductionController.prototype.defaultMenuDataProvider = function (successCallback, errorCallback) {
            var productMenuPath = this.menuDataURI + '/product-menuHierarchy.json';
            ProductEngine.Utils.httpGet(productMenuPath, function (productMenuData, status, headers, config) {
                if (successCallback) {
                    successCallback(productMenuData);
                }
            }, function (productMenuData, status, headers, config) {
                if (errorCallback) {
                    errorCallback(productMenuData);
                }
            });
        };
        return ProductionController;
    }());
    exports.ProductionController = ProductionController;
});
