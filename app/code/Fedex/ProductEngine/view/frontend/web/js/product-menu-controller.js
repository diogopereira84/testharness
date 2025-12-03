define(["require","exports","peProductEngine"], function (require, exports, ProductEngine) {
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var ProductMenuController = (function () {
    function ProductMenuController(menuDataURI) {
        this.menuData = null;
        this.productMenuTranslator = new ProductMenuTranslator();
        this.productProvider = null;
        this.menuDataURI = menuDataURI;
        this.productProvider = this.defaultProductProvider;
    }
    ProductMenuController.prototype.buildMenuPath = function () {
        return this.menuDataURI + '/product-menuHierarchy.json';
    };
    ProductMenuController.prototype.defaultProductProvider = function (callBack, errorCallback) {
        var _this = this;
        ProductEngine.Utils.httpGet(this.buildMenuPath(), function (menuData, status, headers, config) {
            if (menuData) {
                _this.menuData = _this.productMenuTranslator.translateProductMenuData(menuData);
                menuData = _this.productMenuTranslator.displayableProductMenu(_this.menuData);
            }
            if (callBack) {
                callBack(menuData);
            }
        }, function (menuData, status, headers, config) {
            if (errorCallback) {
                errorCallback(menuData);
            }
        });
    };
    ProductMenuController.prototype.fetchMenu = function (cb, ecb) {
        if (!this.productProvider) {
            console.error('A product provider callback must be set before selecting any products');
        }
        else {
            this.productProvider(function (menuData) {
                if (cb) {
                    cb(menuData);
                }
            }, function (data, status, headers) {
                if (ecb) {
                    ecb(data, status, headers);
                }
            });
        }
    };
    ProductMenuController.prototype.searchProduct = function (search) {
        var filteredMenu = new ProductEngine.ArrayList();
        var productMenuData = null;
        var it = this.menuData.productMenuDetails.iterator();
        while (it.hasNext()) {
            var data = it.next();
            if (this.categoryMatch(data, search) && this.purposeMatch(data, search) && this.tagsMatch(data, search)) {
                filteredMenu.add(data);
            }
        }
        productMenuData = {
            productMenuDetails: filteredMenu,
            productMenuDisplay: this.menuData.productMenuDisplay
        };
        return this.productMenuTranslator.displayableProductMenu(productMenuData);
    };
    ProductMenuController.prototype.categoryMatch = function (data, search) {
        var matchFound = false;
        if (search.category) {
            if (data.category === search.category) {
                matchFound = true;
            }
        }
        else {
            matchFound = true;
        }
        return matchFound;
    };
    ProductMenuController.prototype.purposeMatch = function (data, search) {
        var matchFound = false;
        if (search.purpose) {
            var it_1 = search.purpose.iterator();
            while (it_1.hasNext()) {
                var purpose = it_1.next();
                if (data.purpose.contains(purpose)) {
                    matchFound = true;
                    break;
                }
            }
        }
        else {
            matchFound = true;
        }
        return matchFound;
    };
    ProductMenuController.prototype.tagsMatch = function (data, search) {
        var matchFound = false;
        if (search.tags) {
            var it_2 = data.productTags.iterator();
            while (it_2.hasNext()) {
                var tags = it_2.next();
                if (tags.indexOf(search.tags) > 0) {
                    matchFound = true;
                    break;
                }
            }
        }
        else {
            matchFound = true;
        }
        return matchFound;
    };
    ProductMenuController.prototype.retrieveMenuItemSummary = function (id) {
        var productMenuSummary = {};
        if (this.menuData) {
            var it_3 = this.menuData.productMenuDetails.iterator();
            var products = [];
            while (it_3.hasNext()) {
                var data = it_3.next();
                if (data.id === id) {
                    productMenuSummary.promoCode = data.promoCode;
                    if (data.productId) {
                        var productDetails = {
                            productId: data.productId,
                            version: data.version,
                            productPresetId: data.productPresetId
                        };
                        products.push(productDetails);
                    }
                    else {
                        if (data.products) {
                            for (var i = 0; i < data.products.length; i++) {
                                var product = data.products[i];
                                var productDetails = {
                                    productId: product.productId,
                                    version: product.version,
                                    productPresetId: product.productPresetId
                                };
                                products.push(productDetails);
                            }
                        }
                    }
                    productMenuSummary.products = products;
                }
            }
        }
        return productMenuSummary;
    };
    ProductMenuController.prototype.retrieveMenuItemSummaryByControlId = function (id, controlId) {
        var productMenuSummary = {};
        if (this.menuData) {
            var it_4 = this.menuData.productMenuDetails.iterator();
            var products = [];
            while (it_4.hasNext()) {
                var data = it_4.next();
                if (data.id === id) {
                    productMenuSummary.promoCode = data.promoCode;
                    productMenuSummary.applyPrintPromoCode = data.applyPrintPromoCode;
                    if (data.productId) {
                        var productDetails = {
                            productId: data.productId,
                            version: data.version,
                            productPresetId: this.retrievePresetId(controlId, data.presets)
                        };
                        products.push(productDetails);
                    }
                    else {
                        if (data.products) {
                            for (var i = 0; i < data.products.length; i++) {
                                var product = data.products[i];
                                var productDetails = {
                                    productId: product.productId,
                                    version: product.version,
                                    productPresetId: product.productPresetId
                                };
                                products.push(productDetails);
                            }
                        }
                    }
                    productMenuSummary.products = products;
                }
            }
        }
        return productMenuSummary;
    };
    ProductMenuController.prototype.retrievePresetId = function (controlId, presets) {
        if (presets) {
            for (var i = 0; i < presets.length; i++) {
                if (controlId === presets[i].id) {
                    return presets[i].presetId;
                }
            }
        }
        return null;
    };
    return ProductMenuController;
}());
exports.ProductMenuController = ProductMenuController;
var ProductMenuTranslator = (function () {
    function ProductMenuTranslator() {
    }
    ProductMenuTranslator.prototype.translateProductMenuData = function (data) {
        var productMenuData = {
            productMenuDetails: this.translateProductMenuDetails(data.productMenuDetails),
            productMenuDisplay: this.translateProductMenuDisplay(data.productMenuDisplay)
        };
        return productMenuData;
    };
    ProductMenuTranslator.prototype.displayableProductMenu = function (productMenuData) {
        var displayableMenuGroup = {};
        displayableMenuGroup.menuDetailsGroup = [];
        var displayableGroupIds = new ProductEngine.ArrayMap();
        var menuDataIt = productMenuData.productMenuDetails.iterator();
        while (menuDataIt.hasNext()) {
            var menuDetails = menuDataIt.next();
            var menuDisplay = this.getMenuDisplayByRefId(productMenuData.productMenuDisplay.refIdMap, menuDetails.id);
            if (menuDisplay) {
                var displayableMenu = this.buildDisplayableMenu(menuDetails, menuDisplay);
                if (menuDisplay.groupId) {
                    var groupDetails = this.getDisplayableGroup(productMenuData.productMenuDisplay.idMap, menuDisplay.groupId);
                    var displayableGroup = displayableGroupIds.get(groupDetails.id);
                    if (!displayableGroup) {
                        var menuDisplayGroups = {
                            groupName: groupDetails.productName,
                            menuDetails: [displayableMenu]
                        };
                        displayableGroupIds.put(groupDetails.id, menuDisplayGroups);
                    }
                    else {
                        displayableGroup['menuDetails'].push(displayableMenu);
                        displayableGroupIds.put(groupDetails.id, displayableGroup);
                    }
                }
            }
        }
        var displayGroups = displayableGroupIds.values();
        for (var i = 0; i < displayGroups.values.length; i++) {
            displayableMenuGroup['menuDetailsGroup'].push(displayGroups.values[i]);
        }
        return displayableMenuGroup;
    };
    ProductMenuTranslator.prototype.getDisplayableGroupDetails = function (groupId, displayableGroupIds) {
        var displayGroup = displayableGroupIds.get(groupId);
        if (!displayGroup) {
            displayableGroupIds.put(groupId, displayGroup);
        }
        return displayGroup;
    };
    ProductMenuTranslator.prototype.getMenuDisplayByRefId = function (refIdMap, refId) {
        return refIdMap.get(refId);
    };
    ProductMenuTranslator.prototype.getDisplayableGroup = function (idMap, groupId) {
        return idMap.get(groupId);
    };
    ProductMenuTranslator.prototype.translateProductMenuDetails = function (productMenu) {
        var productMenuList = new ProductEngine.ArrayList();
        var menuDetail = null;
        if (productMenu) {
            for (var i = 0; i < productMenu.length; i++) {
                menuDetail = productMenu[i];
                var details = {
                    id: menuDetail.id,
                    name: menuDetail.name,
                    productDetailsId: menuDetail.productDetailsId,
                    productId: menuDetail.productId,
                    version: menuDetail.version,
                    productPresetId: menuDetail.productPresetId,
                    products: menuDetail.products,
                    promoCode: menuDetail.promoCode,
                    applyPrintPromoCode: menuDetail.applyPrintPromoCode,
                    description: menuDetail.desciption,
                    category: menuDetail.category,
                    productTags: this.translateProductTags(menuDetail.productTags),
                    purpose: this.translatePurpose(menuDetail.purpose),
                    controlId: menuDetail.controlId,
                    commercialProduct: menuDetail.commercialProduct,
                    controlIds: this.translatecontrolIds(menuDetail.controlIds),
                    presets: this.translateProductPresets(menuDetail.presets)
                };
                productMenuList.add(details);
            }
        }
        return productMenuList;
    };
    ProductMenuTranslator.prototype.translateProductMenuDisplay = function (productMenuDisplay) {
        var productMenuList = new ProductEngine.ArrayList();
        var refIdMap = new ProductEngine.ArrayMap();
        var idMap = new ProductEngine.ArrayMap();
        var menuDisplay = null;
        if (productMenuDisplay) {
            for (var i = 0; i < productMenuDisplay.length; i++) {
                productMenuList.add(productMenuDisplay[i]);
                refIdMap.put(productMenuDisplay[i].refId, productMenuDisplay[i]);
                idMap.put(productMenuDisplay[i].id, productMenuDisplay[i]);
            }
        }
        menuDisplay = {
            refIdMap: refIdMap,
            idMap: idMap,
            productMenuDisplayList: productMenuList
        };
        return menuDisplay;
    };
    ProductMenuTranslator.prototype.translateProductTags = function (tags) {
        var productTagsList = new ProductEngine.ArrayList();
        for (var i = 0; i < tags.length; i++) {
            var productTags = tags[i];
            productTagsList.add(productTags.name);
        }
        return productTagsList;
    };
    ProductMenuTranslator.prototype.translatePurpose = function (purpose) {
        var productPurposeList = new ProductEngine.ArrayList();
        for (var i = 0; i < purpose.length; i++) {
            var productPurpose = purpose[i];
            productPurposeList.add(productPurpose.name);
        }
        return productPurposeList;
    };
    ProductMenuTranslator.prototype.buildDisplayableMenu = function (menuDetails, menuDisplay) {
        var display = {
            id: menuDetails.id,
            productName: menuDisplay.productName,
            productId: menuDetails.productId,
            version: menuDetails.version,
            productPresetId: menuDetails.productPresetId,
            products: menuDetails.products,
            promoCode: menuDetails.promoCode,
            applyPrintPromoCode: menuDetails.applyPrintPromoCode,
            productDetailsId: menuDetails.productDetailsId,
            desc: menuDisplay.desc,
            imgRef: menuDisplay.imgRef,
            toolTipText: menuDisplay.toolTipText,
            controlId: menuDetails.controlId,
            commercialProduct: menuDetails.commercialProduct,
            controlIds: menuDetails.controlIds,
            presets: menuDetails.presets
        };
        return display;
    };
    ProductMenuTranslator.prototype.translatecontrolIds = function (controlIds) {
        if (controlIds) {
            var controlIdList = [];
            for (var i = 0; i < controlIds.length; i++) {
                var control = { id: controlIds[i].id, name: controlIds[i].name };
                controlIdList.push(control);
            }
            return controlIdList;
        }
        return null;
    };
    ProductMenuTranslator.prototype.translateProductPresets = function (presets) {
        if (presets) {
            var productPresetList = [];
            for (var i = 0; i < presets.length; i++) {
                var productPresets = { id: presets[i].id, presetId: presets[i].presetId };
                productPresetList.push(productPresets);
            }
            return productPresetList;
        }
        return null;
    };
    return ProductMenuTranslator;
}());
});
