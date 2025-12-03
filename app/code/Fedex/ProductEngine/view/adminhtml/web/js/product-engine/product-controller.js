define([
    "require",
    "exports",
    "peProductTranslator",
    "peProductEngine"
], function (require, exports, product_translator_1, ProductEngine) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    var ProductController = (function () {
        function ProductController(productDataURI, hierarchyProvider, productProvider) {
            if (hierarchyProvider === void 0) { hierarchyProvider = null; }
            if (productProvider === void 0) { productProvider = null; }
            this.EVENT_UPDATE_DISPLAYABLE_PRODUCT = 'displayableProductUpdated';
            this.EVENT_UPDATED_SELECTED_PRODUCT = 'selectedProductUpdated';
            this.EVENT_ELEMENT_VALIDATED = 'elementValidated';
            this.productData = null;
            this.product = null;
            this.selectedFeature = null;
            this.selectedChoiceId = null;
            this.contentAdded = null;
            this.supportsPropMap = {};
            this.selectedProduct = new ProductEngine.ProductInstance();
            this.contextMap = null;
            this.displayableProduct = null;
            this.productEngine = new ProductEngine.ProductConfigurationProcessor();
            this.productTranslator = new product_translator_1.ProductTranslator();
            this.displayProcessor = new ProductEngine.ProductDisplayProcessor();
            this.viewOnly = false;
            this.validationResults = [];
            this.eventListeners = {};
            this.productProvider = this.defaultProductProvider;
            this.productDataURI = productDataURI;
        }
        ProductController.prototype.buildProductPath = function (productId, productVersion) {
            var productPath = this.productDataURI + '/product-' + productId;
            if (productVersion != null) {
                if (typeof productVersion === 'number' || (typeof productVersion === 'string' && productVersion.indexOf('v') === -1)) {
                    productVersion = 'v' + productVersion;
                }
                productPath += '-' + productVersion;
            }
            productPath += '.json';
            return productPath;
        };
        ProductController.prototype.defaultProductProvider = function (productId, productVersion, controlId, callBack, errorCallback) {
            var _this = this;
            ProductEngine.Utils.httpGet(this.buildProductPath(productId, productVersion), function (productData, status, headers, config) {
                if (productData != null) {
                    if (controlId) {
                        var keyValue = { controlId: controlId };
                        _this.setProductElementKeyValues(keyValue);
                    }
                    productData = _this.productTranslator.translateProductData(productData, controlId);
                }
                if (callBack) {
                    callBack(productData);
                }
            }, function (productData, status, headers, config) {
                if (errorCallback) {
                    errorCallback(productData);
                }
            });
        };
        ProductController.prototype.on = function (eventName, callback) {
            this.eventListeners[eventName] = callback;
        };
        ProductController.prototype.fire = function (eventName) {
            if (typeof this.eventListeners[eventName] === 'function') {
                this.eventListeners[eventName]();
            }
        };
        ProductController.prototype.fetchProductDataById = function (id, version, controlId, cb, ecb) {
            if (this.productProvider == null) {
                console.error('A product provider callback must be set before selecting any products');
            }
            else {
                this.productProvider(id, version, controlId, function (productData) {
                    if (cb) {
                        cb(productData);
                    }
                }, function (data, status, headers) {
                    if (ecb) {
                        ecb(data, status, headers);
                    }
                });
            }
        };
        ProductController.prototype.buildProductContext = function () {
            var context = new ProductEngine.DefaultProductContext();
            context.setProduct(this.selectedProduct);
            if (this.selectedChoiceId) {
                context.getSelectedChoiceIds().add(this.selectedChoiceId);
            }
            if (this.contextMap) {
                for (var key in this.contextMap) {
                    context.put(key, this.contextMap[key]);
                }
            }
            context.setContentAdded(this.contentAdded);
            return context;
        };
        ProductController.prototype.selectedProductModified = function () {
            this.reconfigureProduct();
            this.fire(this.EVENT_UPDATED_SELECTED_PRODUCT);
        };
        ProductController.prototype.serializeSelectedProduct = function () {
            return this.productTranslator.serializeProductInstance(this.selectedProduct, this.supportsPropMap);
        };
        ProductController.prototype.loadSerializedProduct = function (serializedProduct, viewOnly) {
            if (typeof serializedProduct === 'string') {
                serializedProduct = JSON.parse(serializedProduct);
            }
            if (typeof viewOnly === 'boolean' &&
                viewOnly === true) {
                this.viewOnly = true;
            }
            else {
                this.viewOnly = false;
            }
            this.selectedProduct = this.productTranslator.translateProductInstance(serializedProduct);
            this.reconfigureProduct();
        };
        ProductController.prototype.getProductDisplayByRefId = function (refId) {
            if (this.productData) {
                return this.productData.displays
                    .getProductDisplayByRefId(refId);
            }
            return null;
        };
        ProductController.prototype.getProductDisplayById = function (id) {
            if (this.productData) {
                return this.productData.displays
                    .getProductDisplayById(id);
            }
            return null;
        };
        ProductController.prototype.getPropertyInputDetailsByRefId = function (refId) {
            return this.productData.displays.getPropertyDisplayByRefId(refId);
        };
        ProductController.prototype.getDisplayDetailByRefId = function (refId) {
            return this.getProductDisplayByRefId(refId);
        };
        ProductController.prototype.productElementCopy = function (from, to) {
            var dontCopyProduct = {
                features: 1,
                choices: 1,
                properties: 1,
                contentRequirements: 1,
                compatibilityGroups: 1,
                allowedSizes: 1,
                products: 1,
                featureRefs: 1
            };
            this.productTranslator.elementCopy(from, to, dontCopyProduct);
        };
        ProductController.prototype.copyProductElements = function (from, to) {
            var pitFrom = from.properties.iterator();
            var pitTo = to.properties.iterator();
            var userSpecialInstructions = null;
            var customerSpecialInstructions = null;
            var systemSpecialInstructions = null;
            while (pitFrom.hasNext()) {
                var sourceProperty = pitFrom.next();
                if (sourceProperty.getName() === 'USER_SPECIAL_INSTRUCTIONS' && sourceProperty.getValue() !== null) {
                    userSpecialInstructions = sourceProperty.getValue();
                }
                if (sourceProperty.getName() === 'SYSTEM_SI' && sourceProperty.getValue() !== null) {
                    systemSpecialInstructions = sourceProperty.getValue();
                }
                if (sourceProperty.getName() === 'CUSTOMER_SI' && sourceProperty.getValue() !== null) {
                    customerSpecialInstructions = sourceProperty.getValue();
                }
            }
            while (pitTo.hasNext()) {
                var destinationProperty = pitTo.next();
                if (destinationProperty.getName() === 'USER_SPECIAL_INSTRUCTIONS') {
                    destinationProperty.setValue(userSpecialInstructions);
                }
                if (destinationProperty.getName() === 'SYSTEM_SI') {
                    destinationProperty.setValue(systemSpecialInstructions);
                }
                if (destinationProperty.getName() === 'CUSTOMER_SI') {
                    destinationProperty.setValue(customerSpecialInstructions);
                }
            }
            if (from.contentAssociations.size() > 0) {
                var caItFrom = from.contentAssociations.iterator();
                var contentHints = new ProductEngine.ArrayList();
                while (caItFrom.hasNext()) {
                    var contentHint = new ProductEngine.ContentHint();
                    var contentAssociation = caItFrom.next();
                    contentHint.setContentAssociation(contentAssociation);
                    contentHints.add(contentHint);
                }
                var contentAssociations = this.productEngine.buildContentAssociation(this.product, this.selectedProduct, contentHints);
                to.getContentAssociations().clear();
                to.setContentAssociations(contentAssociations);
                to.setInstanceId(from.getInstanceId());
                to.setUserProductName(from.getUserProductName());
                to.setContextKeys(from.getContextKeys());
            }
            else {
                to.getContentAssociations().clear();
            }
        };
        ProductController.prototype.displayCopy = function (from, to) {
            var dontCopyDisplay = {
                id: 1,
                refId: 1,
                allowedValues: 1,
                displayHints: 1,
                displayTexts: 1,
                displays: 1
            };
            if (from != null) {
                this.productTranslator.elementCopy(from, to, dontCopyDisplay);
                to.displayHints = [];
                var hintIt = from.displayHints.iterator();
                while (hintIt.hasNext()) {
                    to.displayHints.push(hintIt.next());
                }
                to.displayTexts = [];
                var displayIt = from.displayTexts.iterator();
                while (displayIt.hasNext()) {
                    to.displayTexts.push(displayIt.next());
                }
            }
        };
        ProductController.prototype.addDisplayableProperties = function (propertiesIterator, targetArray, targetElement) {
            if (targetElement === void 0) { targetElement = {}; }
            while (propertiesIterator.hasNext()) {
                var property = propertiesIterator.next();
                var input = this.getPropertyInputDetailsByRefId(property.getId());
                if (input) {
                    var displayableProperty = {};
                    targetArray.push(displayableProperty);
                    this.productElementCopy(property, displayableProperty);
                    this.displayCopy(input, displayableProperty);
                    var instanceProperty = this.getPropertyInstanceById(property.id, targetElement);
                    if (instanceProperty && instanceProperty.value) {
                        displayableProperty.value = instanceProperty.value;
                    }
                    displayableProperty.allowedValues = [];
                    var boundIt = property.getBounds().iterator();
                    while (boundIt.hasNext()) {
                        var inBound = boundIt.next();
                        var allowedValueIt = inBound.allowedValues.iterator();
                        while (allowedValueIt.hasNext()) {
                            var propertyAllowedValue = allowedValueIt.next();
                            var displayAllowedValue = null;
                            if (this.viewOnly) {
                                displayAllowedValue = this.addSelectedAllowedValuesForViewOnly(input.allowedValues, propertyAllowedValue, property);
                            }
                            else {
                                displayAllowedValue = this.addDisplayableAllowedValues(input.allowedValues, propertyAllowedValue);
                            }
                            if (displayAllowedValue !== null) {
                                displayableProperty.allowedValues.push(displayAllowedValue);
                            }
                        }
                    }
                    displayableProperty.key = property.name;
                }
            }
        };
        ProductController.prototype.addProductDisplayableProperties = function (targetElement, configuredElement, parent, displayIdMap) {
            var propertyGroupIds = new ProductEngine.ArrayMap();
            var propertiesIterator = configuredElement.properties.iterator();
            while (propertiesIterator.hasNext()) {
                var property = propertiesIterator.next();
                var input = this.getPropertyInputDetailsByRefId(property.getId());
                if (input) {
                    var displayableProperty = {};
                    this.productElementCopy(property, displayableProperty);
                    this.displayCopy(input, displayableProperty);
                    var instanceProperty = this.getPropertyInstanceById(property.id, targetElement);
                    if (instanceProperty && instanceProperty.value) {
                        displayableProperty.value = instanceProperty.value;
                    }
                    displayableProperty.allowedValues = [];
                    var boundIt = property.getBounds().iterator();
                    while (boundIt.hasNext()) {
                        var inBound = boundIt.next();
                        var allowedValueIt = inBound.allowedValues.iterator();
                        while (allowedValueIt.hasNext()) {
                            var propertyAllowedValue = allowedValueIt.next();
                            var displayAllowedValue = this.addDisplayableAllowedValues(input.allowedValues, propertyAllowedValue);
                            if (displayAllowedValue !== null) {
                                displayableProperty.allowedValues.push(displayAllowedValue);
                            }
                        }
                    }
                    displayableProperty.key = property.name;
                    this.addDisplayableElement(displayableProperty, parent, 'properties', 'featureGroups', displayIdMap);
                    if (input.parentId) {
                        var displayGroup = this.getProductDisplayById(input.parentId);
                        if (displayGroup) {
                            var propertyGroup = propertyGroupIds.get(displayGroup.id);
                            if (propertyGroup) {
                                propertyGroup['properties'].push(displayableProperty);
                            }
                            else {
                                var displayPropGroup = {
                                    name: displayGroup.name,
                                    properties: [displayableProperty]
                                };
                                propertyGroupIds.put(displayGroup.Id, displayPropGroup);
                                parent.propertiesGroup.push(displayPropGroup);
                            }
                        }
                    }
                }
            }
        };
        ProductController.prototype.addDisplayableAllowedValues = function (allowedValues, propertyAllowedValue) {
            if (allowedValues) {
                var allowsValuesIterator = allowedValues.iterator();
                while (allowsValuesIterator.hasNext()) {
                    var allowedValue = allowsValuesIterator.next();
                    if (propertyAllowedValue.getName() === allowedValue.getValue()) {
                        return allowedValue;
                    }
                }
            }
            return null;
        };
        ProductController.prototype.addSelectedAllowedValuesForViewOnly = function (allowedValues, propertyAllowedValue, property) {
            var allowedValue = this.addDisplayableAllowedValues(allowedValues, propertyAllowedValue);
            if (allowedValue !== null) {
                if (property.value === allowedValue.value) {
                    return allowedValue;
                }
            }
            return null;
        };
        ProductController.prototype.addDisplayableElement = function (displayableElement, parent, elementArrayName, groupArrayName, displayIdMap) {
            if (displayIdMap === void 0) { displayIdMap = {}; }
            var arrayName = (displayableElement.__displayGroup ? groupArrayName : elementArrayName);
            var groupId = displayableElement.parentId;
            if (groupId != null) {
                var parentGroup = this.getDisplayableGroup(groupId, parent, elementArrayName, groupArrayName, displayIdMap);
                if (parentGroup != null) {
                    parentGroup[arrayName].push(displayableElement);
                }
                else {
                    parent[arrayName].push(displayableElement);
                }
            }
            else {
                parent[arrayName].push(displayableElement);
            }
        };
        ProductController.prototype.getDisplayableGroup = function (groupId, parent, elementArrayName, groupArrayName, displayIdMap) {
            var displayableGroup = displayIdMap[groupId];
            if (displayableGroup == null) {
                var displayGroupDef = this.getProductDisplayById(groupId);
                if (displayGroupDef != null) {
                    displayableGroup = { __displayGroup: true };
                    displayableGroup[elementArrayName] = [];
                    displayableGroup[groupArrayName] = [];
                    this.displayCopy(displayGroupDef, displayableGroup);
                    displayIdMap[groupId] = displayableGroup;
                    this.addDisplayableElement(displayableGroup, parent, elementArrayName, groupArrayName, displayIdMap);
                }
            }
            else if (!displayableGroup[elementArrayName]) {
                displayableGroup[elementArrayName] = [];
            }
            return displayableGroup;
        };
        ProductController.prototype.addDisplayablePageExceptions = function (selectedProduct, configuredProduct, parent, displayIdMap) {
            var _this = this;
            var cfgPe = null;
            var selectedPe = null;
            var displayableCfgPe = null;
            var displayableSelectedPe = null;
            var propertyIt = null;
            var property = null;
            var peIt = null;
            var peSelectedIt = null;
            var exceptionType = null;
            var tabsInsertsRange = this.getTabsInsertRanges();
            var fileRanges = this.getAllFilePageRanges();
            var totalPageCount = 0;
            fileRanges.forEach(function (caRanges) {
                totalPageCount = caRanges.end;
            });
            peIt = configuredProduct.pageExceptions.iterator();
            var _loop_1 = function () {
                cfgPe = peIt.next();
                propertyIt = cfgPe.properties.iterator();
                while (propertyIt.hasNext()) {
                    property = propertyIt.next();
                    if (String(property.name) === 'EXCEPTION_TYPE') {
                        exceptionType = property.value;
                        break;
                    }
                }
                if (this_1.getDisplayDetailByRefId(cfgPe.id)) {
                    displayableCfgPe = {};
                    displayableCfgPe.exceptionType = exceptionType;
                    this_1.productElementCopy(cfgPe, displayableCfgPe);
                    this_1.displayCopy(this_1.getDisplayDetailByRefId(cfgPe.id), displayableCfgPe);
                    displayableCfgPe.featureGroups = [];
                    var tempInstance = this_1.productEngine.buildPageExceptionInstance(cfgPe);
                    displayableCfgPe.properties = [];
                    if (cfgPe.properties) {
                        this_1.addDisplayableProperties(cfgPe.properties.iterator(), displayableCfgPe.properties, tempInstance);
                    }
                    var peDisplayIdMap = {};
                    if (exceptionType === 'PRINTING_EXCEPTION') {
                        var cfgPeList_1 = [];
                        cfgPe.features.elements.forEach(function (cfgFe) {
                            cfgFe.defaultChoiceName = (_this.getDisplayDetailByRefId(cfgFe.defaultChoiceId)).name;
                            cfgPeList_1.push(cfgFe);
                        });
                        cfgPe.features.elements = cfgPeList_1;
                    }
                    this_1.addDisplayableFeatures(tempInstance, cfgPe, displayableCfgPe, peDisplayIdMap);
                    var selected = [];
                    peSelectedIt = selectedProduct.pageExceptions.iterator();
                    var _loop_2 = function () {
                        selectedPe = peSelectedIt.next();
                        if (Number(selectedPe.id) === Number(cfgPe.id)) {
                            displayableSelectedPe = {};
                            this_1.productElementCopy(cfgPe, displayableSelectedPe);
                            this_1.displayCopy(this_1.getDisplayDetailByRefId(cfgPe.id), displayableSelectedPe);
                            displayableSelectedPe.properties = [];
                            if (cfgPe.properties) {
                                this_1.addDisplayableProperties(cfgPe.properties.iterator(), displayableCfgPe.properties, selectedPe);
                            }
                            displayableSelectedPe.featureGroups = [];
                            displayableSelectedPe.features = [];
                            var selectedPeDisplayIdMap = {};
                            if (exceptionType === 'PRINTING_EXCEPTION') {
                                var updatedSelectedPe = this_1.productEngine.addProductLevelFeature(cfgPe, selectedPe, this_1.selectedProduct);
                                this_1.addDisplayableFeatures(updatedSelectedPe, cfgPe, displayableSelectedPe, selectedPeDisplayIdMap);
                            }
                            else {
                                this_1.addDisplayableFeatures(selectedPe, cfgPe, displayableSelectedPe, selectedPeDisplayIdMap);
                            }
                            this_1.addDisplayablePageRange(selectedPe, displayableSelectedPe);
                            displayableSelectedPe.instanceId = selectedPe.instanceId;
                            if (exceptionType === 'PRINTING_EXCEPTION') {
                                var count = this_1.getTabsInsertCount(selectedPe, tabsInsertsRange);
                                var endRange_1 = this_1.getFileEndRange(selectedPe, count);
                                fileRanges.forEach(function (peFilesRanges) {
                                    var startRange = endRange_1 - (peFilesRanges.end - peFilesRanges.start);
                                    if (peFilesRanges.start === startRange) {
                                        displayableSelectedPe.fileName = peFilesRanges.fileName;
                                        displayableSelectedPe.contentType = peFilesRanges.contentType;
                                        displayableSelectedPe.contentPageRange = { start: startRange, end: endRange_1 };
                                    }
                                });
                            }
                            else {
                                var rangeIt_1 = selectedPe.ranges.iterator().next();
                                var count_1 = 0;
                                tabsInsertsRange.forEach(function (virtualTabsInsertRanges) {
                                    if (virtualTabsInsertRanges < rangeIt_1.start) {
                                        count_1++;
                                    }
                                });
                                displayableSelectedPe.docPageRanges = [];
                                var pageRanges = [];
                                var ranges = {};
                                if ((rangeIt_1.start - count_1) > totalPageCount) {
                                    ranges = { start: (rangeIt_1.start - count_1) - 1, end: (rangeIt_1.start - count_1) - 1, position: 'AFTER' };
                                }
                                else {
                                    ranges = { start: (rangeIt_1.start - count_1), end: (rangeIt_1.start - count_1), position: 'BEFORE' };
                                }
                                pageRanges.push(ranges);
                                displayableSelectedPe.docPageRanges = pageRanges;
                            }
                            selected.push(displayableSelectedPe);
                        }
                    };
                    while (peSelectedIt.hasNext()) {
                        _loop_2();
                    }
                    displayableCfgPe.selected = selected;
                    this_1.addDisplayableElement(displayableCfgPe, parent, 'pageExceptions', 'featureGroups', displayIdMap);
                }
            };
            var this_1 = this;
            while (peIt.hasNext()) {
                _loop_1();
            }
        };
        ProductController.prototype.addDisplayablePageRange = function (selectedPe, displayableSelectedPe) {
            displayableSelectedPe.pageRanges = [];
            var pageRanges = [];
            var it = selectedPe.ranges.iterator();
            while (it.hasNext()) {
                var selectedRanges = it.next();
                var ranges = {};
                ranges.start = selectedRanges.start;
                ranges.end = selectedRanges.end;
                pageRanges.push(ranges);
            }
            displayableSelectedPe.pageRanges = pageRanges;
        };
        ProductController.prototype.addDisplayableFeatures = function (targeElement, configuredElement, parent, displayIdMap) {
            var feature = null;
            var featureDisplayDetails = null;
            var displayableFeature = null;
            var choiceIt = null;
            var choice = null;
            var choiceDisplayDetails = null;
            var displayableChoice = null;
            var propertyIt = null;
            parent.features = [];
            var featureIt = configuredElement.features.iterator();
            while (featureIt.hasNext()) {
                feature = featureIt.next();
                displayableFeature = {
                    choiceGroups: [],
                    choices: [],
                    contentReqRefIds: [],
                    locked: this.isFeatureLocked(feature)
                };
                if (this.viewOnly) {
                    if (!this.containsChoice(feature, targeElement)) {
                        continue;
                    }
                }
                this.productElementCopy(feature, displayableFeature);
                featureDisplayDetails = this.getDisplayDetailByRefId(feature.id);
                if (featureDisplayDetails != null) {
                    this.displayCopy(featureDisplayDetails, displayableFeature);
                    this.addDisplayableElement(displayableFeature, parent, 'features', 'featureGroups', displayIdMap);
                    choiceIt = feature.choices.iterator();
                    while (choiceIt.hasNext()) {
                        choice = choiceIt.next();
                        if (this.viewOnly &&
                            !this.isChoiceSelected(choice, targeElement)) {
                            continue;
                        }
                        displayableChoice = { properties: [], selected: (this.isChoiceSelected(choice, targeElement)), contentReqRefIds: [] };
                        this.productElementCopy(choice, displayableChoice);
                        choiceDisplayDetails = this.getDisplayDetailByRefId(choice.id);
                        if (choiceDisplayDetails != null) {
                            this.displayCopy(choiceDisplayDetails, displayableChoice);
                            this.addDisplayableElement(displayableChoice, displayableFeature, 'choices', 'choiceGroups', displayIdMap);
                            if (displayableChoice.selected) {
                                displayableFeature.selectedChoice = displayableChoice;
                            }
                            propertyIt = choice.properties.iterator();
                            this.addDisplayableProperties(propertyIt, displayableChoice.properties, targeElement);
                        }
                    }
                }
            }
        };
        ProductController.prototype.addSkuDisplay = function (targetElement, configuredElement, parent, displayIdMap) {
            var skuDisplayIterator = configuredElement.displays.skuDisplays.iterator();
            while (skuDisplayIterator.hasNext()) {
                var skuDisplay = skuDisplayIterator.next();
                var displayableSku = {};
                this.displayCopy(skuDisplay, displayableSku);
                displayableSku.skus = [];
                var externalSkuList = targetElement.externalSkus.iterator();
                while (externalSkuList.hasNext()) {
                    var externalSku = externalSkuList.next();
                    if (externalSku.price === undefined) {
                        externalSku.price = null;
                    }
                    if (externalSku.skuDescription === undefined) {
                        externalSku.skuDescription = null;
                    }
                    if (externalSku.unitPrice === undefined) {
                        externalSku.unitPrice = null;
                    }
                    displayableSku.skus.push(externalSku);
                }
                this.addDisplayableElement(displayableSku, parent, 'skuDetails', 'featureGroups', displayIdMap);
            }
        };
        ProductController.prototype.addValueDisplay = function (targetElement, configuredElement, parent, displayIdMap) {
            var valueDisplayIterator = configuredElement.displays.valueDisplays.iterator();
            while (valueDisplayIterator.hasNext()) {
                var valueDisplay = valueDisplayIterator.next();
                var valueObject = {};
                var displayableValue = {};
                this.displayCopy(valueDisplay, displayableValue);
                displayableValue.valueDetails = [];
                if (valueDisplay.valueType === 'WEIGHT') {
                    if (configuredElement.product.getExternalRequirements() !== null) {
                        displayableValue.required = configuredElement.product.getExternalRequirements().isWeightRequired();
                    }
                    if (targetElement.getExternalProductionDetails() !== null) {
                        if (targetElement.getExternalProductionDetails().getWeight() !== null) {
                            if (targetElement.getExternalProductionDetails().getWeight().getValue() !== 0 &&
                                targetElement.getExternalProductionDetails().getWeight().getValue() !== null) {
                                valueObject.name = valueDisplay.valueType;
                                valueObject.value = targetElement.getExternalProductionDetails().getWeight().getValue();
                                valueObject.unit = targetElement.getExternalProductionDetails().getWeight().getUnits().name();
                                displayableValue.valueDetails.push(valueObject);
                            }
                        }
                    }
                }
                if (valueDisplay.valueType === 'TIME') {
                    if (configuredElement.product.getExternalRequirements() !== null) {
                        displayableValue.required = configuredElement.product.getExternalRequirements().isProductionTimeRequired();
                    }
                    if (targetElement.getExternalProductionDetails() !== null) {
                        if (targetElement.getExternalProductionDetails().getProductionTime() !== null) {
                            if (targetElement.getExternalProductionDetails().getProductionTime().getValue() !== 0 &&
                                targetElement.getExternalProductionDetails().getProductionTime().getValue() !== null) {
                                valueObject.name = valueDisplay.valueType;
                                valueObject.value = targetElement.getExternalProductionDetails().getProductionTime().getValue();
                                valueObject.unit = targetElement.getExternalProductionDetails().getProductionTime().getUnits().name();
                                displayableValue.valueDetails.push(valueObject);
                            }
                        }
                    }
                }
                this.addDisplayableElement(displayableValue, parent, 'fulfillmentDetails', 'featureGroups', displayIdMap);
            }
        };
        ProductController.prototype.containsChoice = function (feature, targeElement) {
            var choice = null;
            var choiceIt = feature.choices.iterator();
            while (choiceIt.hasNext()) {
                choice = choiceIt.next();
                if (this.isChoiceSelected(choice, targeElement)) {
                    return true;
                }
            }
            return false;
        };
        ProductController.prototype.updateDisplayableProduct = function () {
            this.displayableProduct = {};
            var displayIdMap = {};
            this.productElementCopy(this.product, this.displayableProduct);
            this.displayCopy(this.getDisplayDetailByRefId(this.product.id), this.displayableProduct);
            this.displayableProduct.userProductName = this.selectedProduct.userProductName;
            this.displayableProduct.featureGroups = [];
            this.addDisplayableFeatures(this.selectedProduct, this.product, this.displayableProduct, displayIdMap);
            this.displayableProduct.properties = [];
            this.displayableProduct.propertiesGroup = [];
            if (this.product.properties != null) {
                this.addProductDisplayableProperties(this.selectedProduct, this.product, this.displayableProduct, displayIdMap);
            }
            if (this.productData.displays.skuDisplays != null) {
                this.addSkuDisplay(this.selectedProduct, this.productData, this.displayableProduct, displayIdMap);
            }
            if (this.productData.displays.valueDisplays != null) {
                this.addValueDisplay(this.selectedProduct, this.productData, this.displayableProduct, displayIdMap);
            }
            this.displayableProduct.pageExceptions = [];
            this.addDisplayablePageExceptions(this.selectedProduct, this.product, this.displayableProduct, displayIdMap);
            if (this.selectedProduct.contentAssociations.length !== 0) {
                var content = null;
                var contentAssociationsIterator = this.selectedProduct.contentAssociations.iterator();
                while (contentAssociationsIterator.hasNext()) {
                    content = contentAssociationsIterator.next();
                    if (content.physicalContent === true) {
                        this.displayableProduct.hasPhysicalContent = true;
                        break;
                    }
                }
            }
            this.fire(this.EVENT_UPDATE_DISPLAYABLE_PRODUCT);
        };
        ProductController.prototype.getSelectedProduct = function () {
            return this.selectedProduct;
        };
        ProductController.prototype.getValidationResults = function () {
            return this.productEngine.getValidationResultsForProduct(this.product, this.productData.rules, this
                .buildProductContext());
        };
        ProductController.prototype.initSelectedProduct = function () {
            this.selectedProduct = this.productEngine.buildProductInstance(this.product);
            this.selectedProductModified();
        };
        ProductController.prototype.reconfigureProduct = function () {
            if (this.product) {
                var prdContext = this.buildProductContext();
                this.product = this.productEngine.reconfigureProduct(this.productData.product, this.productData.rules, prdContext);
                this.selectedProduct = prdContext.getProduct();
                this.validationResults = this.productTranslator.serializeValidationResults(this.getValidationResults());
                this.updateDisplayableProduct();
            }
        };
        ProductController.prototype.getProductPresetById = function (presetId) {
            var preset = null;
            if (this.productData.presets) {
                var it_1 = this.productData.presets.iterator();
                while (it_1.hasNext()) {
                    preset = it_1.next();
                    if (preset.id === presetId) {
                        return preset;
                    }
                }
            }
            return null;
        };
        ProductController.prototype.getProductPresetName = function (presetId) {
            if (presetId) {
                if (this.productData.presets) {
                    var it_2 = this.productData.presets.iterator();
                    while (it_2.hasNext()) {
                        var preset = it_2.next();
                        if (preset.id === presetId) {
                            return preset.name;
                        }
                    }
                }
            }
            return null;
        };
        ProductController.prototype.applyPreset = function (presetId) {
            var preset = this.getProductPresetById(presetId);
            if (preset == null) {
                throw Error('Preset not found in product for preset id ' + presetId);
            }
            this.productEngine.applyPresetToProductInstance(preset, this.productData.product, this.buildProductContext());
            this.selectedProductModified();
        };
        ProductController.prototype.getFeatureById = function (featureId) {
            var feature = this.product.features.get(featureId);
            if (feature == null) {
                feature = this.getFeatureByIdFromPageExceptions(featureId);
            }
            return feature;
        };
        ProductController.prototype.getFeatureByIdFromPageExceptions = function (featureId) {
            var it = this.product.pageExceptions.iterator();
            var feature = null;
            while (it.hasNext()) {
                feature = it.next().features.get(featureId);
                if (feature != null) {
                    return feature;
                }
            }
        };
        ProductController.prototype.selectProductById = function (productId, productVersion, controlId, successCallback, errorCallback) {
            var _this = this;
            this.resolveProductVersion(productId, productVersion, false, function (result) {
                _this.fetchProductDataById(productId, result.version, controlId, function (productData) {
                    _this.productData = productData;
                    _this.selectProduct(productData.product);
                    var alert = {
                        code: null,
                        message: null,
                        alertType: null
                    };
                    if (result.active === false) {
                        alert.code = 'PRODUCT.VERSION.INACTIVE';
                        alert.message = 'The product version is inactive';
                        alert.alertType = 'WARNING';
                    }
                    var alerts = [];
                    alerts.push(alert);
                    var productResult = {
                        alerts: []
                    };
                    productResult.alerts = alerts;
                    var productResponse = {
                        productResult: null
                    };
                    productResponse.productResult = productResult;
                    if (successCallback) {
                        successCallback(productResponse);
                    }
                }, errorCallback);
            });
        };
        ProductController.prototype.selectProductByHint = function (productHint, successCallback, errorCallback) {
            var _this = this;
            if (productHint.validateProductConfig === null) {
                productHint.validateProductConfig = true;
            }
            var deserializedHint = this.productTranslator.translateProductHint(productHint);
            deserializedHint.productId = this.productEngine.resolveProductId(deserializedHint);
            this.resolveProductVersion(deserializedHint.productId, productHint.version, true, function (result) {
                deserializedHint.version = result.version;
                _this.fetchProductDataById(deserializedHint.productId, deserializedHint.version, deserializedHint.controlId, function (productData) {
                    _this.productData = productData;
                    _this.product = productData.product;
                    _this.selectedProduct = _this.productEngine.buildProductInstance(_this.product);
                    _this.applyProductHints(deserializedHint);
                    var alert = {
                        code: null,
                        message: null,
                        alertType: null
                    };
                    if (result.active === false) {
                        alert.code = 'PRODUCT.VERSION.INACTIVE';
                        alert.message = 'The product version is inactive';
                        alert.alertType = 'WARNING';
                    }
                    var alerts = [];
                    alerts.push(alert);
                    if (successCallback) {
                        successCallback(alerts);
                    }
                }, errorCallback);
            });
        };
        ProductController.prototype.resolveProductVersion = function (productId, version, isHints, callBack) {
            var result = {
                version: null,
                active: false
            };
            this.defaultMenuDataProvider(function (productMenuData) {
                if (productMenuData.productMenuDetails) {
                    var resolvedVersionNum = 0;
                    var resolvedProductVersion = null;
                    for (var i = 0; i < productMenuData.productMenuDetails.length; i++) {
                        if (productMenuData.productMenuDetails[i].productId === productId.toString()) {
                            if (productMenuData.productMenuDetails[i].version === null) {
                                if (1 > resolvedVersionNum) {
                                    resolvedVersionNum = 1;
                                }
                            }
                            else {
                                if (Number(productMenuData.productMenuDetails[i].version.substring(1)) > resolvedVersionNum) {
                                    resolvedVersionNum = Number(productMenuData.productMenuDetails[i].version.substring(1));
                                    resolvedProductVersion = productMenuData.productMenuDetails[i].version;
                                }
                            }
                        }
                    }
                    if (resolvedVersionNum > 0) {
                        var productInputVersion = 0;
                        if (version === undefined || version === null) {
                            if (isHints === true) {
                                productInputVersion = resolvedVersionNum;
                                result.version = resolvedProductVersion;
                            }
                            else {
                                productInputVersion = 1;
                                result.version = version;
                            }
                        }
                        else if (typeof version === 'string') {
                            result.version = version;
                            if (version.indexOf('v') === -1) {
                                productInputVersion = Number(version);
                            }
                            else {
                                productInputVersion = Number(version.substring(1));
                            }
                        }
                        else {
                            result.version = version;
                            productInputVersion = version;
                        }
                        if (result.version === 1 || result.version === '1') {
                            result.version = null;
                        }
                        if (resolvedVersionNum === productInputVersion) {
                            result.active = true;
                        }
                        else {
                            result.active = false;
                        }
                    }
                }
                callBack(result);
            }, function (productMenuData) {
                callBack(result);
            });
        };
        ProductController.prototype.defaultMenuDataProvider = function (successCallBack, errorCallback) {
            var productMenuPath = this.productDataURI + '/product-menuHierarchy.json';
            ProductEngine.Utils.httpGet(productMenuPath, function (productMenuData, status, headers, config) {
                if (successCallBack) {
                    successCallBack(productMenuData);
                }
            }, function (productMenuData, status, headers, config) {
                if (errorCallback) {
                    errorCallback(productMenuData);
                }
            });
        };
        ProductController.prototype.applyProductHints = function (deserializedHint) {
            if (deserializedHint.presetId && deserializedHint.presetId !== 0) {
                var preset = this.getProductPresetById(deserializedHint.presetId);
                if (preset == null) {
                    throw Error('Preset not found in product for preset id ' + deserializedHint.presetId);
                }
                this.productEngine.applyPresetToProductInstance(preset, this.productData.product, this.buildProductContext());
            }
            if (deserializedHint.choiceIds) {
                this.productEngine.applyChoiceIds(deserializedHint.choiceIds, this.product, this.selectedProduct);
            }
            if ((deserializedHint.contentHints && deserializedHint.contentHints.size() > 0) || (deserializedHint.defaultContent && deserializedHint.defaultContent === true)) {
                var contentAssociation = this.productEngine.buildContentAssociation(this.product, this.selectedProduct, deserializedHint.contentHints);
                this.selectedProduct.setContentAssociations(contentAssociation);
                this.contentAdded = true;
            }
            if (deserializedHint.qty && deserializedHint.qty !== 0) {
                this.selectedProduct.setQty(deserializedHint.qty);
            }
            if (deserializedHint.instanceId && deserializedHint.instanceId !== 0) {
                this.selectedProduct.setInstanceId(deserializedHint.instanceId);
            }
            if (deserializedHint.getSourceProduct() !== null) {
                this.copyProductElements(deserializedHint.getSourceProduct(), this.selectedProduct);
            }
            if (deserializedHint.validateProductConfig === true || deserializedHint.validateProductConfig === 'true') {
                this.selectedProductModified();
            }
            else {
                this.fire(this.EVENT_UPDATED_SELECTED_PRODUCT);
            }
            this.contentAdded = false;
        };
        ProductController.prototype.getQuantitySets = function (productKey, successCallback, errorCallback) {
            var _this = this;
            var quantitySet = [];
            this.resolveProductVersion(productKey.id, productKey.version, false, function (productVersion) {
                _this.fetchProductDataById(productKey.id, productVersion, null, function (productData) {
                    var property = _this.getPropertyByName('PRODUCT_QTY_SET', productData.product.properties);
                    if (property != null) {
                        var boundIt = property.getBounds().iterator();
                        while (boundIt.hasNext()) {
                            var inBound = boundIt.next();
                            var allowedValueIt = inBound.allowedValues.iterator();
                            while (allowedValueIt.hasNext()) {
                                quantitySet.push(allowedValueIt.next().name);
                            }
                        }
                    }
                    if (successCallback) {
                        successCallback(quantitySet);
                    }
                }, errorCallback);
            });
        };
        ProductController.prototype.selectChoicesByIds = function (choiceIds) {
            if (choiceIds) {
                var deserializedChoiceIds = this.productTranslator.translateArrayToList(choiceIds);
                this.productEngine.applyChoiceIds(deserializedChoiceIds, this.product, this.selectedProduct);
                if (!(this.selectedProduct.contentAssociations && this.selectedProduct.contentAssociations.size() > 0 && this.selectedProduct.getContentAssociations().get(0).getContentReference() != null)) {
                    var contentAssociation = this.productEngine.buildContentAssociation(this.product, this.selectedProduct, null);
                    this.selectedProduct.setContentAssociations(contentAssociation);
                }
                this.selectedProductModified();
            }
        };
        ProductController.prototype.selectProduct = function (product) {
            this.product = product;
            this.initSelectedProduct();
        };
        ProductController.prototype.selectFeature = function (feature) {
            if (this.selectedFeature != null && (feature == null || this.isFeatureIdSelected(feature.id))) {
                this.selectedFeature = null;
            }
            else {
                this.selectedFeature = feature;
            }
        };
        ProductController.prototype.selectFeatureById = function (featureId) {
            this.selectFeature(this.getFeatureById(featureId));
        };
        ProductController.prototype.isFeatureSelected = function (feature) {
            return this.isFeatureIdSelected(feature.id);
        };
        ProductController.prototype.isFeatureIdSelected = function (featureId) {
            return (this.selectedFeature != null && this.selectedFeature.id === featureId);
        };
        ProductController.prototype.selectChoiceById = function (choiceId, featureId, targetElement) {
            var feature = this.getFeatureById(featureId);
            var choice = feature.choices.get(choiceId);
            this.selectedChoiceId = choiceId;
            if (choice) {
                this.selectChoice(choice, feature, targetElement);
                this.selectedProductModified();
            }
            this.selectedChoiceId = null;
        };
        ProductController.prototype.selectChoice = function (choice, feature, targetElement) {
            targetElement = this.resolveTargetElement(targetElement);
            this.productEngine.addChoiceToInstance(choice, feature, targetElement);
        };
        ProductController.prototype.isChoiceSelected = function (choice, targetElement) {
            return this.isChoiceIdSelected(choice.id, targetElement);
        };
        ProductController.prototype.isChoiceIdSelected = function (id, targetElement) {
            return this.resolveTargetElement(targetElement).containsChoice(id);
        };
        ProductController.prototype.getSelectedChoiceForFeatureId = function (featureId, targetElement) {
            return this.productEngine.getSelectedChoiceForFeatureId(featureId, this.resolveTargetElement(targetElement));
        };
        ProductController.prototype.isFeatureLocked = function (feature) {
            var it = feature.choices.iterator();
            var choice = null;
            var count = 0;
            while (it.hasNext()) {
                choice = it.next();
                if (choice.isSelectable()) {
                    count++;
                }
                if (count > 1) {
                    return false;
                }
            }
            return true;
        };
        ProductController.prototype.resolveTargetElement = function (targetElement) {
            if (targetElement) {
                return targetElement;
            }
            else {
                return this.selectedProduct;
            }
        };
        ProductController.prototype.getPropertyById = function (propertyId, properties) {
            var it = null;
            var property = null;
            it = properties.iterator();
            while (it.hasNext()) {
                property = it.next();
                if (Number(property.id) === Number(propertyId)) {
                    return property;
                }
            }
            return null;
        };
        ProductController.prototype.getPropertyByName = function (propertyName, properties) {
            var it = null;
            var property = null;
            it = properties.iterator();
            while (it.hasNext()) {
                property = it.next();
                if (property.name === propertyName) {
                    return property;
                }
            }
            return null;
        };
        ProductController.prototype.getPropertyInstanceById = function (propertyId, targetElement) {
            var property = null;
            targetElement = this.resolveTargetElement(targetElement);
            if (targetElement.properties) {
                property = this.getPropertyById(propertyId, targetElement.properties);
            }
            if (property) {
                return property;
            }
            if (targetElement.features) {
                var fit_1 = targetElement.features.iterator();
                while (fit_1.hasNext()) {
                    var feature = fit_1.next();
                    property = this.getPropertyById(propertyId, feature.choice.properties);
                    if (property) {
                        return property;
                    }
                }
            }
            if (targetElement.feature) {
                property = this.getPropertyById(propertyId, targetElement.feature.choice.properties);
                if (property) {
                    return property;
                }
            }
        };
        ProductController.prototype.setPropertyValueById = function (propertyId, value, targetElement) {
            var property = this.getPropertyInstanceById(propertyId, targetElement);
            if (property != null) {
                property.setValue(value);
                if (property.getName() === 'SYSTEM_SI') {
                    this.selectedProduct.addContextKeys('MANUAL_PRODUCTION_NOTE');
                }
                this.selectedProductModified();
            }
        };
        ProductController.prototype.setPropertyValueByIds = function (properties, targetElement) {
            this.setPropertyValues(properties, targetElement);
            this.selectedProductModified();
        };
        ProductController.prototype.setPropertyValues = function (properties, targetElement) {
            var propLength = properties.length;
            for (var propIndex = 0; propIndex < propLength; propIndex++) {
                var property = properties[propIndex];
                if (property.hasOwnProperty('id') && property.hasOwnProperty('value')) {
                    var propertyInstance = this.getPropertyInstanceById(property.id, targetElement);
                    if (propertyInstance != null) {
                        propertyInstance.setValue(property.value);
                        if (propertyInstance.getName() === 'SYSTEM_SI') {
                            this.selectedProduct.addContextKeys('MANUAL_PRODUCTION_NOTE');
                        }
                    }
                }
            }
        };
        ProductController.prototype.setProductQty = function (qty) {
            this.selectedProduct.qty = ProductEngine.Utils.convertToInteger(qty);
            this.selectedProductModified();
        };
        ProductController.prototype.setUserProductName = function (userProductName) {
            this.selectedProduct.userProductName = userProductName;
            this.updateDisplayableProduct();
        };
        ProductController.prototype.addPageException = function (pageExceptions) {
            var _this = this;
            if (pageExceptions) {
                var oldtabsInsertRanges_1 = this.getTabsInsertRanges();
                var filePageRangeList_1 = this.getAllFilePageRanges();
                var hasTabsInserts_1 = false;
                if (pageExceptions.length === 0) {
                    hasTabsInserts_1 = true;
                    var removePageExceptionList = [];
                    if (this.selectedProduct.pageExceptions.size() > 0) {
                        var pageExceptionIt = this.selectedProduct.pageExceptions.iterator();
                        while (pageExceptionIt.hasNext()) {
                            var pageException = pageExceptionIt.next();
                            var pageExceptionPropIt = pageException.properties.iterator();
                            while (pageExceptionPropIt.hasNext()) {
                                var pageExceptionProp = pageExceptionPropIt.next();
                                if (pageExceptionProp.value === 'TAB' || pageExceptionProp.value === 'INSERT') {
                                    removePageExceptionList.push(pageException);
                                    break;
                                }
                            }
                        }
                    }
                    if (removePageExceptionList.length === 1) {
                        this.selectedProduct.removePageException(removePageExceptionList[0]);
                    }
                }
                else {
                    var hasPrintException_1 = false;
                    this.removeTabsInsert(pageExceptions);
                    var newTabsInsertList_1 = this.getNewTabsInsertPageRanges(pageExceptions);
                    pageExceptions.forEach(function (pageException) {
                        if (pageException.applyAll !== 'undefined' && pageException.applyAll === true) {
                            _this.removeAllPrintExceptions();
                            if (pageException.choices) {
                                pageException.choices.forEach(function (peFeatures) {
                                    var peFeature = _this.getFeatureById(peFeatures.featureId);
                                    if (peFeature) {
                                        var peChoice = peFeature.choices.get(peFeatures.choiceId);
                                        if (peChoice) {
                                            _this.selectChoice(peChoice, peFeature, null);
                                        }
                                    }
                                });
                            }
                        }
                        else if (pageException.action !== 'undefined' && pageException.action === 'UPDATE') {
                            var printExceptionFileRangesList = _this.getPrintExceptions(filePageRangeList_1, oldtabsInsertRanges_1);
                            printExceptionFileRangesList.forEach(function (printExceptionFileRanges) {
                                if (printExceptionFileRanges.fileName === pageException.fileName && printExceptionFileRanges.contentType === pageException.contentType) {
                                    if (pageException.choices) {
                                        pageException.choices.forEach(function (updatePrintException) {
                                            var updateFeature = _this.getFeatureById(updatePrintException.featureId);
                                            if (updateFeature) {
                                                var updateChoice = updateFeature.choices.get(updatePrintException.choiceId);
                                                if (updateChoice) {
                                                    if (_this.isChoiceSelected(updateChoice, null) && _this.getFeatureByIdFromPageExceptions(updatePrintException.featureId)) {
                                                        var targetElement = _this.resolveTargetElement(printExceptionFileRanges.pe);
                                                        _this.productEngine.removeChoiceFromInstance(updateChoice, updateFeature, targetElement);
                                                    }
                                                    else if (!_this.isChoiceSelected(updateChoice, null)) {
                                                        _this.selectChoice(updateChoice, updateFeature, printExceptionFileRanges.pe);
                                                        if (updatePrintException.properties) {
                                                            var updateChoiceProperties = updateChoice.getProperties();
                                                            if (updateChoiceProperties) {
                                                                _this.setPropertyValues(updatePrintException.properties, printExceptionFileRanges.pe);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }
                            });
                        }
                        else {
                            var configuredIt = _this.product.pageExceptions.iterator();
                            var peInstance_1 = null;
                            while (configuredIt.hasNext()) {
                                var configuredPE = configuredIt.next();
                                if (Number(pageException.pageExceptionId) === Number(configuredPE.id)) {
                                    peInstance_1 = _this.productEngine.buildPageExceptionInstance(configuredPE);
                                    break;
                                }
                            }
                            if (peInstance_1) {
                                peInstance_1.setHasContent(pageException.hasContent);
                                peInstance_1.setInstanceId(pageException.instanceId);
                                if (pageException.pageRanges) {
                                    hasTabsInserts_1 = true;
                                    for (var j = 0; j < pageException.pageRanges.length; j++) {
                                        if (pageException.pageRanges[j].position !== 'undefined' && pageException.pageRanges[j].position !== null) {
                                            var virtualPageRange = _this.convertToVirtualPages(pageException.pageRanges[j], newTabsInsertList_1);
                                            peInstance_1.addRange(_this.productTranslator
                                                .translatePageRange(virtualPageRange));
                                        }
                                        else {
                                            peInstance_1.addRange(_this.productTranslator
                                                .translatePageRange(pageException.pageRanges[j]));
                                        }
                                    }
                                }
                                else if (pageException.fileName && pageException.contentType) {
                                    filePageRangeList_1.forEach(function (file) {
                                        if (file.fileName === pageException.fileName && file.contentType === pageException.contentType) {
                                            var startCount_1 = 0;
                                            var endCount_1 = 0;
                                            oldtabsInsertRanges_1.forEach(function (range) {
                                                if (file.start + startCount_1 >= range) {
                                                    startCount_1++;
                                                }
                                                if (file.end + startCount_1 >= range) {
                                                    endCount_1++;
                                                }
                                            });
                                            var totalCount_1 = 0;
                                            oldtabsInsertRanges_1.forEach(function (endRange) {
                                                if ((file.end + endCount_1) >= endRange) {
                                                    totalCount_1++;
                                                }
                                            });
                                            oldtabsInsertRanges_1.forEach(function (tabsInsertRange) {
                                                if ((file.end + totalCount_1) === tabsInsertRange) {
                                                    totalCount_1++;
                                                }
                                            });
                                            var pageRange = { start: file.start + startCount_1, end: file.end + totalCount_1 };
                                            peInstance_1.addRange(_this.productTranslator.translatePageRange(pageRange));
                                            hasPrintException_1 = true;
                                        }
                                    });
                                    if (oldtabsInsertRanges_1.length > 0) {
                                        hasTabsInserts_1 = true;
                                    }
                                }
                                _this.selectedProduct.addPageException(peInstance_1);
                                if (pageException.choices) {
                                    for (var j = 0; j < pageException.choices.length; j++) {
                                        var featureChoice = pageException.choices[j];
                                        var feature = _this.getFeatureById(featureChoice.featureId);
                                        if (feature) {
                                            var choice = feature.choices.get(featureChoice.choiceId);
                                            if (choice) {
                                                if ((hasPrintException_1 && !_this.isChoiceSelected(choice, null)) || !hasPrintException_1) {
                                                    _this.selectChoice(choice, feature, peInstance_1);
                                                    if (featureChoice.properties) {
                                                        var choiceProperties = choice.getProperties();
                                                        if (choiceProperties) {
                                                            _this.setPropertyValues(featureChoice.properties, peInstance_1);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                if (pageException.properties) {
                                    _this.setPropertyValues(pageException.properties, peInstance_1);
                                }
                            }
                        }
                    });
                }
                if (hasTabsInserts_1) {
                    var newPrintExceptionList = this.getPrintExceptions(filePageRangeList_1, oldtabsInsertRanges_1);
                    if (newPrintExceptionList.length > 0) {
                        var newTabsInsertRanges = this.getTabsInsertRanges();
                        this.updatePrintExceptionWithNewPageRanges(newPrintExceptionList, newTabsInsertRanges);
                        var newPageExceptionList = this.productEngine.updatePrintingExceptionPageRange(this.selectedProduct);
                        this.removeAllPageExceptions();
                        this.selectedProduct.pageExceptions = newPageExceptionList;
                    }
                }
                this.selectedProductModified();
            }
        };
        ProductController.prototype.removeAllPageExceptions = function () {
            this.selectedProduct.removeAllPageExceptions();
            this.selectedProductModified();
        };
        ProductController.prototype.removePageException = function (exception) {
            this.selectedProduct.removePageException(exception);
            this.selectedProductModified();
        };
        ProductController.prototype.removePageExceptionAt = function (exception) {
            this.selectedProduct.removePageExceptionAt(exception);
            this.selectedProductModified();
        };
        ProductController.prototype.getPageExceptions = function () {
            var fes = [];
            var it = this.selectedProduct.getPageExceptions().iterator();
            while (it.hasNext()) {
                fes.push(this.productTranslator.serializePageExceptionInstance(it.next()));
            }
            return fes;
        };
        ProductController.prototype.removeTabsInsert = function (pageExceptions) {
            var _this = this;
            var removePageExceptionList = [];
            var hasTabInsert = false;
            var configuredIt = this.product.pageExceptions.iterator();
            while (configuredIt.hasNext()) {
                var configuredPE = configuredIt.next();
                for (var i = 0; i < pageExceptions.length; i++) {
                    var pe = pageExceptions[i];
                    if (Number(pe.pageExceptionId) === Number(configuredPE.id)) {
                        var pePropIt = configuredPE.properties.iterator();
                        while (pePropIt.hasNext()) {
                            var peProp = pePropIt.next();
                            if (peProp.value === 'TAB' || peProp.value === 'INSERT') {
                                hasTabInsert = true;
                                break;
                            }
                        }
                    }
                }
            }
            if (this.selectedProduct.pageExceptions.size() > 0 && hasTabInsert) {
                var pageExceptionIt = this.selectedProduct.pageExceptions.iterator();
                while (pageExceptionIt.hasNext()) {
                    var pageException = pageExceptionIt.next();
                    var pageExceptionPropIt = pageException.properties.iterator();
                    while (pageExceptionPropIt.hasNext()) {
                        var pageExceptionProp = pageExceptionPropIt.next();
                        if (pageExceptionProp.value === 'TAB' || pageExceptionProp.value === 'INSERT') {
                            removePageExceptionList.push(pageException);
                        }
                    }
                }
                if (removePageExceptionList.length > 0) {
                    removePageExceptionList.forEach(function (removeException) {
                        _this.selectedProduct.removePageException(removeException);
                    });
                }
            }
        };
        ProductController.prototype.removeAllPrintExceptions = function () {
            var _this = this;
            var printExceptionList = [];
            if (this.selectedProduct.pageExceptions.size() > 0) {
                var pageExceptionIt = this.selectedProduct.pageExceptions.iterator();
                while (pageExceptionIt.hasNext()) {
                    var pageException = pageExceptionIt.next();
                    var pageExceptionPropIt = pageException.properties.iterator();
                    while (pageExceptionPropIt.hasNext()) {
                        var pageExceptionProp = pageExceptionPropIt.next();
                        if (pageExceptionProp.value === 'PRINTING_EXCEPTION') {
                            printExceptionList.push(pageException);
                        }
                    }
                }
                if (printExceptionList.length > 0) {
                    printExceptionList.forEach(function (removeException) {
                        _this.selectedProduct.removePageException(removeException);
                    });
                }
            }
        };
        ProductController.prototype.removePrintExceptionByFileName = function (printException) {
            var _this = this;
            if (printException) {
                var tabsInsertRanges = this.getTabsInsertRanges();
                var fileRanges = this.getAllFilePageRanges();
                var peIt = this.selectedProduct.pageExceptions.iterator();
                var _loop_3 = function () {
                    var pe = peIt.next();
                    var pePropIt = pe.properties.iterator();
                    var _loop_4 = function () {
                        var peProp = pePropIt.next();
                        if (peProp.value === 'PRINTING_EXCEPTION') {
                            var tabsInsertCount = this_2.getTabsInsertCount(pe, tabsInsertRanges);
                            var endRange_2 = this_2.getFileEndRange(pe, tabsInsertCount);
                            fileRanges.forEach(function (peFilesRanges) {
                                var startRange = endRange_2 - (peFilesRanges.end - peFilesRanges.start);
                                if (peFilesRanges.start === startRange && peFilesRanges.fileName === printException.fileName && peFilesRanges.contentType === printException.contentType) {
                                    _this.removePageException(pe);
                                }
                            });
                        }
                    };
                    while (pePropIt.hasNext()) {
                        _loop_4();
                    }
                };
                var this_2 = this;
                while (peIt.hasNext()) {
                    _loop_3();
                }
            }
        };
        ProductController.prototype.getTabsInsertRanges = function () {
            var selectedPeIt = this.selectedProduct.pageExceptions.iterator();
            var tabsInsertRanges = [];
            while (selectedPeIt.hasNext()) {
                var selectedPe = selectedPeIt.next();
                var pePropIt = selectedPe.properties.iterator();
                while (pePropIt.hasNext()) {
                    var peProp = pePropIt.next();
                    if (peProp.value === 'TAB' || peProp.value === 'INSERT') {
                        var rangeIt = selectedPe.ranges.iterator();
                        while (rangeIt.hasNext()) {
                            var index = 0;
                            var range = rangeIt.next();
                            for (var i = 0; i < tabsInsertRanges.length; i++) {
                                if (range.start < tabsInsertRanges[i]) {
                                    break;
                                }
                                index++;
                            }
                            tabsInsertRanges.splice(index, 0, range.start);
                        }
                    }
                }
            }
            return tabsInsertRanges;
        };
        ProductController.prototype.getAllFilePageRanges = function () {
            var caIt = this.selectedProduct.contentAssociations.iterator();
            var filePageRange = {};
            var filePageRangeList = [];
            var startRange = 0;
            var pageCount = 0;
            var endRange = 0;
            while (caIt.hasNext()) {
                var ca = caIt.next();
                var caPageRangeIt = ca.getPageGroups().iterator();
                while (caPageRangeIt.hasNext()) {
                    var caPageRange = caPageRangeIt.next();
                    startRange = pageCount + caPageRange.start;
                    endRange = pageCount + (caPageRange.end - caPageRange.start) + 1;
                    pageCount = pageCount + caPageRange.end;
                    filePageRange = { start: startRange, end: endRange, fileName: ca.fileName, contentType: ca.contentType };
                    filePageRangeList.push(filePageRange);
                }
            }
            return filePageRangeList;
        };
        ProductController.prototype.getTabsInsertCount = function (pe, tabsInsertRanges) {
            var count = 0;
            var rangesIt = pe.ranges.iterator();
            var _loop_5 = function () {
                count = 0;
                var range = rangesIt.next();
                if (tabsInsertRanges.length > 0) {
                    tabsInsertRanges.forEach(function (startRange) {
                        if (range.end > startRange) {
                            count++;
                        }
                    });
                }
            };
            while (rangesIt.hasNext()) {
                _loop_5();
            }
            return count;
        };
        ProductController.prototype.getFileEndRange = function (pe, count) {
            var peRangeIt = pe.ranges.iterator();
            var newRange = 0;
            while (peRangeIt.hasNext()) {
                var range = peRangeIt.next();
                newRange = range.end - count;
            }
            return newRange;
        };
        ProductController.prototype.flattenProperties = function (elementProperties, propertiesMap) {
            if (elementProperties != null) {
                for (var i = 0; i < elementProperties.length; i++) {
                    var property = elementProperties[i];
                    if (property.name) {
                        propertiesMap[property.name] = property.value;
                    }
                }
            }
        };
        ProductController.prototype.getChoiceProperties = function (choice) {
            var properties = [];
            if (choice.properties) {
                properties.push.apply(properties, this.convertToArray(choice.properties));
            }
            return properties;
        };
        ProductController.prototype.getFeatureProperties = function (features) {
            var properties = [];
            var feature = null;
            var fit = null;
            if (features) {
                fit = features.iterator();
                while (fit.hasNext()) {
                    feature = fit.next();
                    properties.push.apply(properties, this.getChoiceProperties(feature.choice));
                }
            }
            return properties;
        };
        ProductController.prototype.getFlattenedPageGroup = function (pageGroup) {
            var pageGroupMap = {};
            this.productTranslator.elementCopy(pageGroup, pageGroupMap);
            return pageGroupMap;
        };
        ProductController.prototype.getFlattenedContent = function (content) {
            var contentMap = {};
            var pageGroups = [];
            this.productTranslator.elementCopy(content, contentMap, { pageGroups: true });
            if (content.pageGroups) {
                var it_3 = content.pageGroups.iterator();
                while (it_3.hasNext()) {
                    pageGroups.push(this.getFlattenedPageGroup(it_3.next()));
                }
                contentMap.pageGroups = pageGroups;
            }
            return contentMap;
        };
        ProductController.prototype.convertToArray = function (obj) {
            var arr = [];
            if (obj != null && obj.size() > 0) {
                var it_4 = obj.iterator();
                while (it_4.hasNext()) {
                    arr.push(it_4.next());
                }
            }
            return arr;
        };
        ProductController.prototype.getFlattenedProduct = function () {
            return this.getFlattenedProductForInstance(this.selectedProduct);
        };
        ProductController.prototype.getFlattenedProductForInstance = function (productInstance) {
            return this.serializeFlattenedProduct(this.productEngine.getFlattenedProduct(productInstance, this.product));
        };
        ProductController.prototype.serializeFlattenedProduct = function (flattenedProduct) {
            var contents = [];
            var pageExceptions = [];
            var fileRanges = this.getAllFilePageRanges();
            var tabsInsertRanges = this.getTabsInsertRanges();
            flattenedProduct.properties = flattenedProduct.properties.valsByKey;
            if (flattenedProduct.contents) {
                var it_5 = flattenedProduct.contents.iterator();
                while (it_5.hasNext()) {
                    contents.push(this.getFlattenedContent(it_5.next()));
                }
                flattenedProduct.contents = contents;
            }
            if (flattenedProduct.pageExceptions) {
                var it_6 = flattenedProduct.pageExceptions.iterator();
                var _loop_6 = function () {
                    var pageException = it_6.next();
                    var pageRanges = [];
                    if (pageException.ranges) {
                        var rangeIt = pageException.ranges.iterator();
                        while (rangeIt.hasNext()) {
                            var pageRangeMap = {};
                            this_3.productTranslator.elementCopy(rangeIt.next(), pageRangeMap, {});
                            pageRanges.push(pageRangeMap);
                        }
                    }
                    pageException.ranges = pageRanges;
                    pageException.properties = pageException.properties.valsByKey;
                    if (pageException.name === 'PRINTING_EXCEPTION') {
                        var count_2 = 0;
                        pageException.ranges.forEach(function (countRange) {
                            count_2 = 0;
                            tabsInsertRanges.forEach(function (startRange) {
                                if (countRange.end > startRange) {
                                    count_2++;
                                }
                            });
                        });
                        var endRange_3 = 0;
                        pageException.ranges.forEach(function (range) {
                            endRange_3 = range.end - count_2;
                        });
                        fileRanges.forEach(function (caRanges) {
                            if (caRanges.start === endRange_3 - (caRanges.end - caRanges.start)) {
                                pageException.contentPageRange = { start: caRanges.start, end: caRanges.end };
                            }
                        });
                    }
                    pageExceptions.push(pageException);
                };
                var this_3 = this;
                while (it_6.hasNext()) {
                    _loop_6();
                }
            }
            flattenedProduct.pageExceptions = pageExceptions;
            return flattenedProduct;
        };
        ProductController.prototype.getDefaultProperties = function (productData) {
            var pi = this.productEngine.buildProductInstance(productData.product);
            var context = new ProductEngine.DefaultProductContext();
            context.setProduct(pi);
            var cp = this.productEngine.getConfiguredProduct(productData.product, productData.rules, context);
            this.productEngine.applyConfiguredProductToInstance(cp, pi, true);
            return (this.getFlattenedProductForInstance(pi)).properties;
        };
        ProductController.prototype.getContentAssociations = function () {
            var cas = [];
            var it = this.selectedProduct.getContentAssociations().iterator();
            while (it.hasNext()) {
                cas.push(this.productTranslator.serializeContentAssociationInstance(it.next()));
            }
            return cas;
        };
        ProductController.prototype.addContentAssociation = function (ca) {
            this.addContentToContainer(ca);
            this.contentAdded = true;
            this.selectedProductModified();
            this.contentAdded = false;
        };
        ProductController.prototype.addContentToContainer = function (ca) {
            this.selectedProduct.addContentAssociation(this.productTranslator.translateContentAssociationInstance(ca));
        };
        ProductController.prototype.updateContentAssociation = function (ca) {
            this.updateContentInContainer(ca);
            this.selectedProductModified();
        };
        ProductController.prototype.updateContentInContainer = function (ca) {
            var it = this.selectedProduct.contentAssociations.iterator();
            var caInstance = null;
            while (it.hasNext()) {
                caInstance = it.next();
                if ((caInstance.parentContentReference === ca.parentContentReference) &&
                    (caInstance.contentReqId === ca.contentReqId)) {
                    caInstance.setContentReference(ca.contentReference);
                    caInstance.setContentReplacementUrl(ca.contentReplacementUrl);
                    caInstance.setContentType(ca.contentType);
                    caInstance.setFileName(ca.fileName);
                    caInstance.setPrintReady(ca.printReady);
                    if (ca.pageGroups) {
                        var groups = new ProductEngine.ArrayList();
                        for (var i = 0; i < ca.pageGroups.length; i++) {
                            groups.add(this.productTranslator.translatePageGroup(ca.pageGroups[i]));
                        }
                        caInstance.setPageGroups(groups);
                    }
                    if (ca.specialInstructions) {
                        caInstance.setSpecialInstructions(ca.specialInstructions);
                    }
                    if (ca.fileSource && ca.fileSource !== undefined) {
                        caInstance.setFileSource(ca.fileSource);
                    }
                    if (ca.physicalContent && ca.physicalContent !== undefined && ca.physicalContent !== null) {
                        caInstance.setPhysicalContent(ProductEngine.Utils.convertToBoolean(ca.physicalContent));
                    }
                }
            }
        };
        ProductController.prototype.removeContentAssociation = function (ca) {
            this.removeContentFromContainer(ca);
            this.selectedProductModified();
        };
        ProductController.prototype.removeContentFromContainer = function (ca) {
            var it = this.selectedProduct.contentAssociations.iterator();
            var caInstance = null;
            while (it.hasNext()) {
                caInstance = it.next();
                if ((caInstance.contentReqId === ca.contentReqId) &&
                    (ca.parentContentReference !== null) &&
                    (caInstance.parentContentReference === ca.parentContentReference)) {
                    this.selectedProduct.removeContentAssociation(caInstance);
                    break;
                }
            }
        };
        ProductController.prototype.removeAllContentByParentContentReference = function (parentContentReference) {
            var cas = [];
            var it = this.selectedProduct.contentAssociations.iterator();
            while (it.hasNext()) {
                var ca = it.next();
                if (ca.parentContentReference === parentContentReference) {
                    cas.push(ca);
                }
            }
            for (var i = 0; i < cas.length; i++) {
                this.selectedProduct.removeContentAssociation(cas[i]);
            }
            this.selectedProductModified();
        };
        ProductController.prototype.processContentAssociations = function (cas) {
            var _this = this;
            if (cas) {
                var filePageRangeList = this.getAllFilePageRanges();
                var tabsInsertRanges = this.getTabsInsertRanges();
                var printExceptionList = this.getPrintExceptions(filePageRangeList, tabsInsertRanges);
                var caAddList_1 = [];
                cas.forEach(function (ca) {
                    if (ca.action === 'ADD') {
                        caAddList_1.push(ca);
                    }
                });
                if (caAddList_1.length === 1) {
                    printExceptionList.forEach(function (printException) {
                        if (printException.fileName === caAddList_1[0].ca.fileName) {
                            var featureArr = printException.pe.features.elements;
                            featureArr.forEach(function (features) {
                                if (features) {
                                    var feature = _this.getFeatureById(features.id);
                                    if (feature) {
                                        var choice = feature.choices.get(features.choice.id);
                                        if (choice) {
                                            _this.selectChoice(choice, feature, null);
                                        }
                                    }
                                }
                            });
                        }
                    });
                    this.removeAllPrintExceptions();
                }
                else {
                    if (printExceptionList.length > 0) {
                        this.removePrintException(caAddList_1, filePageRangeList, printExceptionList, tabsInsertRanges);
                        var updatedPrintExceptionList_1 = this.getPrintExceptions(filePageRangeList, tabsInsertRanges);
                        var newStartPage_1 = 0;
                        var newEndPage_1 = 0;
                        var newCount_1 = 0;
                        var index_1 = 0;
                        var newCaList_1 = [];
                        caAddList_1.forEach(function (caAdd) {
                            caAdd.ca.pageGroups.forEach(function (pageGroup) {
                                newStartPage_1 = newCount_1 + pageGroup.start;
                                newEndPage_1 = newCount_1 + pageGroup.end;
                                newCount_1 = newCount_1 + (newEndPage_1 - newStartPage_1) + 1;
                            });
                            var newCa = { start: newStartPage_1, end: newEndPage_1, fileName: caAdd.ca.fileName, contentType: caAdd.ca.contentType, replacedFileName: caAdd.ca.replacedFileName, replacedContentType: caAdd.ca.replacedContentType, index: index_1 };
                            newCaList_1.push(newCa);
                            index_1++;
                        });
                        var newPrintExceptionRanges_1 = [];
                        newCaList_1.forEach(function (ca) {
                            updatedPrintExceptionList_1.forEach(function (updatedPe) {
                                if (ca.fileName === updatedPe.fileName && ca.contentType === updatedPe.contentType) {
                                    var newPrintException = { start: ca.start, end: ca.end, pe: updatedPe.pe, fileName: ca.fileName, contentType: ca.contentType, index: ca.index };
                                    newPrintExceptionRanges_1.push(newPrintException);
                                }
                                else if ((ca.replacedFileName !== undefined && ca.replacedFileName !== null) && (ca.replacedContentType !== undefined && ca.replacedContentType !== null)) {
                                    if (ca.replacedFileName === updatedPe.fileName && ca.replacedContentType === updatedPe.contentType) {
                                        var newPrintException = { start: ca.start, end: ca.end, pe: updatedPe.pe, fileName: ca.fileName, contentType: ca.contentType, index: ca.index };
                                        newPrintExceptionRanges_1.push(newPrintException);
                                    }
                                }
                            });
                        });
                        this.updatePrintExceptionWithNewPageRanges(newPrintExceptionRanges_1, tabsInsertRanges);
                        var newPageExceptionList = this.productEngine.updatePrintingExceptionPageRange(this.selectedProduct);
                        this.removeAllPageExceptions();
                        this.selectedProduct.pageExceptions = newPageExceptionList;
                    }
                }
                this.processContentInContainer(cas);
                this.selectedProductModified();
                this.contentAdded = false;
            }
        };
        ProductController.prototype.isSizeSameAsDefault = function (properties) {
            var defaultProperties = this.getDefaultProperties(this.productData);
            return (Number(defaultProperties.MEDIA_WIDTH) === Number(properties.MEDIA_WIDTH) &&
                (Number(defaultProperties.MEDIA_HEIGHT) === Number(properties.MEDIA_HEIGHT)));
        };
        ProductController.prototype.hasPrintReadyContent = function (id) {
            var contentAssociations = this.selectedProduct.getContentAssociations();
            if (contentAssociations) {
                var it_7 = contentAssociations.iterator();
                while (it_7.hasNext()) {
                    var ca = it_7.next();
                    if ((id === ca.getContentReqId()) && ca.printReady) {
                        return true;
                    }
                }
            }
            return false;
        };
        ProductController.prototype.addSizeHints = function (hints, contentRequirement, properties, isDefaultHints) {
            if ((!isDefaultHints) || (contentRequirement.resizeIfDefault)) {
                if (properties.PAGE_ORIENTATION && String(properties.PAGE_ORIENTATION).toUpperCase() === 'LANDSCAPE') {
                    hints.targetWidthInInches = properties.MEDIA_HEIGHT;
                    hints.targetHeightInInches = properties.MEDIA_WIDTH;
                }
                else {
                    hints.targetWidthInInches = properties.MEDIA_WIDTH;
                    hints.targetHeightInInches = properties.MEDIA_HEIGHT;
                }
            }
        };
        ProductController.prototype.addDpiHint = function (hints, properties) {
            if (properties.MIN_DPI) {
                hints.minDPI = properties.MIN_DPI;
            }
        };
        ProductController.prototype.addContentOrientationHint = function (hints, properties) {
            if (properties.LOCK_CONTENT_ORIENTATION) {
                hints.lockContentOrientation = properties.LOCK_CONTENT_ORIENTATION;
            }
        };
        ProductController.prototype.getAvailableContentRequirements = function () {
            return this.convertToArray(this.product.getContentRequirements());
        };
        ProductController.prototype.addImpositionHint = function (hints, properties) {
            if (properties.PRINTS_PER_PAGE) {
                hints.nUpType = properties.PRINTS_PER_PAGE;
            }
        };
        ProductController.prototype.addDefaultImageSizeHint = function (hints, properties) {
            if (properties.DEFAULT_IMAGE_WIDTH) {
                hints.defaultImageWidthInInches = properties.DEFAULT_IMAGE_WIDTH;
            }
            if (properties.DEFAULT_IMAGE_HEIGHT) {
                hints.defaultImageHeightInInches = properties.DEFAULT_IMAGE_HEIGHT;
            }
        };
        ProductController.prototype.getContentConversionHints = function (contentRequirement, isDefaultHints) {
            if (isDefaultHints === void 0) { isDefaultHints = false; }
            if (!contentRequirement) {
                throw Error('ContentRequirement required for constructing conversion hints');
            }
            var hints = {};
            var flattenedProduct = this.getFlattenedProduct();
            this.addSizeHints(hints, contentRequirement, flattenedProduct.properties, isDefaultHints);
            this.addContentOrientationHint(hints, flattenedProduct.properties);
            this.addDpiHint(hints, flattenedProduct.properties);
            this.addDefaultImageSizeHint(hints, flattenedProduct.properties);
            return hints;
        };
        ProductController.prototype.generateProductSummary = function () {
            var productInstanceSummary = this.productEngine.generateProductSummary(this.selectedProduct);
            return this.serializeProductSummary(productInstanceSummary);
        };
        ProductController.prototype.serializeProductSummary = function (productInstanceSummary) {
            var serializedProductSummary = {};
            var productInstOpts = null;
            var piOptionsList = [];
            serializedProductSummary.productName = productInstanceSummary.productName;
            serializedProductSummary.userProductName = productInstanceSummary.userProductName;
            var it = productInstanceSummary.details.iterator();
            var piOptions = null;
            while (it.hasNext()) {
                productInstOpts = {};
                piOptions = it.next();
                productInstOpts.fileName = piOptions.fileName;
                productInstOpts.type = piOptions.type;
                productInstOpts.featureChoiceMap = piOptions.featureChoiceMap.valsByKey;
                piOptionsList.push(productInstOpts);
            }
            serializedProductSummary.productInstOptionsSummary = piOptionsList;
            return serializedProductSummary;
        };
        ProductController.prototype.reorderContentAssociations = function (ca, toIndex) {
            var it = this.selectedProduct.contentAssociations.iterator();
            var caInstance = null;
            var index = 0;
            var selectedCa;
            while (it.hasNext()) {
                caInstance = it.next();
                if ((String(caInstance.parentContentReference).toLowerCase() === String(ca.parentContentReference).toLowerCase()) &&
                    (String(caInstance.contentReqId).toLowerCase() === String(ca.contentReqId).toLowerCase())) {
                    selectedCa = caInstance;
                    break;
                }
                index = index + 1;
            }
            this.selectedProduct.contentAssociations.remove(index);
            this.selectedProduct.contentAssociations.addAtIndex(toIndex, selectedCa);
            this.updateDisplayableProduct();
            this.fire(this.EVENT_UPDATED_SELECTED_PRODUCT);
        };
        ProductController.prototype.getFlattenedProductInstance = function () {
            return this.productEngine.getFlattenedProduct(this.selectedProduct);
        };
        ProductController.prototype.processPageExceptions = function (pageExceptions) {
            this.validatePageExceptions(pageExceptions);
            if (pageExceptions) {
                for (var i = 0; i < pageExceptions.length; i++) {
                    var pe = pageExceptions[i];
                    var peInstance = null;
                    var fe = null;
                    if (pe.action === 'ADD') {
                        fe = this.product.getPageExceptionById(pe.pageExceptionId);
                        peInstance = this.productEngine.buildPageExceptionInstance(fe);
                        this.selectedProduct.addPageException(peInstance);
                    }
                    else if (pe.action === 'UPDATE') {
                        peInstance = this.selectedProduct.getPageExceptions().get(pe.index);
                    }
                    else if (pe.action === 'DELETE') {
                        this.selectedProduct.removePageExceptionAt(pe.index);
                    }
                }
            }
            this.selectedProductModified();
        };
        ProductController.prototype.validatePageExceptions = function (pageExceptions) {
            for (var i = 0; i < pageExceptions.length; i++) {
                var pe = pageExceptions[i];
                if (pe.action == null) {
                    throw Error('Action is required');
                }
                if (pe.pageExceptionId == null) {
                    throw Error('PageExceptionId is required');
                }
            }
        };
        ProductController.prototype.validatePageExceptionPosition = function (pageExceptions) {
            var _this = this;
            var validationResult = null;
            var peInstanceList = [];
            for (var i = 0; i < pageExceptions.length; i++) {
                var pe = pageExceptions[i];
                var fe = this.product.getPageExceptionById(pe.pageExceptionId);
                var peInstance = this.productEngine.buildPageExceptionInstance(fe);
                if (peInstance) {
                    if (pe.pageRanges) {
                        for (var j = 0; j < pe.pageRanges.length; j++) {
                            peInstance.addRange(this.productTranslator
                                .translatePageRange(pe.pageRanges[j]));
                        }
                    }
                }
                this.selectedProduct.addPageException(peInstance);
                peInstanceList.push(peInstance);
            }
            validationResult = this.productEngine
                .validatePageExceptionPosition(this.productData.rules, this
                    .buildProductContext());
            peInstanceList.forEach(function (peInstance) {
                _this.selectedProduct.removePageException(peInstance);
            });
            return validationResult;
        };
        ProductController.prototype.getFeatureChoiceProperties = function (propertyName) {
            var configFeature = this.getConfigFeatureChoiceProperties(propertyName);
            if (configFeature) {
                return this.serializeFeatureChoiceProperties(configFeature);
            }
            return null;
        };
        ProductController.prototype.getConfigFeatureChoiceProperties = function (propertyName) {
            if (propertyName) {
                var configFeatureItr = this.product.features.iterator();
                while (configFeatureItr.hasNext()) {
                    var configFeature = configFeatureItr.next();
                    var configChoicesItr = configFeature.choices.iterator();
                    while (configChoicesItr.hasNext()) {
                        var configChoice = configChoicesItr.next();
                        var configPropertyItr = configChoice.properties.iterator();
                        while (configPropertyItr.hasNext()) {
                            var configProperty = configPropertyItr.next();
                            if (String(configProperty.name).toLowerCase() === String(propertyName).toLowerCase()) {
                                return configFeature;
                            }
                        }
                    }
                }
            }
            return null;
        };
        ProductController.prototype.serializeFeatureChoiceProperties = function (configFeature) {
            if (configFeature.selectable) {
                var featureChoiceProp = {};
                var choices = [];
                var featureDisplay = this.getDisplayDetailByRefId(configFeature.id);
                featureChoiceProp.featureId = configFeature.id;
                featureChoiceProp.featureName = featureDisplay.name;
                var choiceItr = configFeature.choices.iterator();
                while (choiceItr.hasNext()) {
                    var configChoice = choiceItr.next();
                    if (configChoice.selectable) {
                        var choiceDisplay = this.getDisplayDetailByRefId(configChoice.id);
                        if (choiceDisplay) {
                            var choice = {};
                            var properties = [];
                            choice.choiceId = configChoice.id;
                            choice.choiceName = choiceDisplay.name;
                            var configPropItr = configChoice.properties.iterator();
                            while (configPropItr.hasNext()) {
                                var configProp = configPropItr.next();
                                var property = {};
                                property.name = configProp.name;
                                property.value = configProp.value;
                                properties.push(property);
                            }
                            choice.properties = properties;
                            choices.push(choice);
                        }
                    }
                }
                featureChoiceProp.choices = choices;
                return featureChoiceProp;
            }
            return null;
        };
        ProductController.prototype.getProductDisplay = function (controlId, productInstance) {
            if (typeof productInstance === 'string') {
                productInstance = JSON.parse(productInstance);
            }
            var deserialized = this.productTranslator.translateProductInstance(productInstance);
            return this.displayProcessor.productDisplay(this.productData, deserialized, controlId);
        };
        ProductController.prototype.getProductName = function () {
            var display = this.getDisplayDetailByRefId(this.product.id);
            if (display) {
                return display.name;
            }
            return null;
        };
        ProductController.prototype.setProductElementKeyValues = function (keyValue) {
            if (keyValue) {
                if (!this.contextMap) {
                    this.contextMap = {};
                }
                for (var key in keyValue) {
                    this.contextMap[key] = keyValue[key];
                }
            }
        };
        ProductController.prototype.processContentInContainer = function (cas) {
            var _this = this;
            cas.forEach(function (content) {
                if (content.action === 'ADD') {
                    _this.contentAdded = true;
                    _this.addContentToContainer(content.ca);
                }
                else if (content.action === 'UPDATE') {
                    _this.updateContentInContainer(content.ca);
                }
                else if (content.action === 'DELETE') {
                    _this.removeContentFromContainer(content.ca);
                }
            });
        };
        ProductController.prototype.getPrintExceptions = function (filePageRangeList, tabsInsertRanges) {
            var _this = this;
            var printExceptionList = [];
            filePageRangeList.forEach(function (ca) {
                var peIndex = 0;
                var printExcepIt = _this.selectedProduct.pageExceptions.iterator();
                while (printExcepIt.hasNext()) {
                    var printExcep = printExcepIt.next();
                    var printExceptPropIt = printExcep.properties.iterator();
                    while (printExceptPropIt.hasNext()) {
                        var printExceptProp = printExceptPropIt.next();
                        if (printExceptProp.value === 'PRINTING_EXCEPTION') {
                            var tabsInsertCount = _this.getTabsInsertCount(printExcep, tabsInsertRanges);
                            var endRange = _this.getFileEndRange(printExcep, tabsInsertCount);
                            var startRange = endRange - (ca.end - ca.start);
                            if (ca.start === startRange) {
                                var printException = { start: ca.start, end: ca.end, fileName: ca.fileName, contentType: ca.contentType, pe: printExcep, index: peIndex };
                                printExceptionList.push(printException);
                            }
                        }
                    }
                    peIndex++;
                }
            });
            return printExceptionList;
        };
        ProductController.prototype.updatePrintExceptionWithNewPageRanges = function (printExcepList, tabsInsertRanges) {
            var _this = this;
            printExcepList.forEach(function (printExcep) {
                _this.selectedProduct.removePageException(printExcep.pe);
                var updatedPePageRange = _this.getUpdatedPrintExceptionwithNewPageRanges(printExcep, tabsInsertRanges);
                _this.selectedProduct.pageExceptions.addAtIndex(printExcep.index, updatedPePageRange);
            });
        };
        ProductController.prototype.getUpdatedPrintExceptionwithNewPageRanges = function (oldPrintExcep, tabsInsertRanges) {
            if (oldPrintExcep) {
                var startCount_2 = 0;
                var endCount_2 = 0;
                var totalCount_2 = 0;
                tabsInsertRanges.forEach(function (startRange) {
                    if (oldPrintExcep.start + startCount_2 >= startRange) {
                        startCount_2++;
                    }
                    if (oldPrintExcep.end + startCount_2 >= startRange) {
                        endCount_2++;
                    }
                });
                tabsInsertRanges.forEach(function (endRange) {
                    if ((oldPrintExcep.end + endCount_2) >= endRange) {
                        totalCount_2++;
                    }
                });
                tabsInsertRanges.forEach(function (tabsInsertRange) {
                    if ((oldPrintExcep.end + totalCount_2) === tabsInsertRange) {
                        totalCount_2++;
                    }
                });
                return this.productEngine.updatePageRange(oldPrintExcep.pe, oldPrintExcep.start + startCount_2, oldPrintExcep.end + totalCount_2);
            }
        };
        ProductController.prototype.removePrintException = function (caAddList, filePageRangeList, printExceptionList, tabsInsertRanges) {
            var _this = this;
            var removePrintExcep = true;
            var removedFileList = [];
            filePageRangeList.forEach(function (files) {
                removePrintExcep = true;
                caAddList.forEach(function (caAdd) {
                    if ((files.fileName === caAdd.ca.fileName && files.contentType === caAdd.ca.contentType) ||
                        (caAdd.ca.replacedFileName === files.fileName && caAdd.ca.replacedContentType === files.contentType)) {
                        removePrintExcep = false;
                    }
                });
                if (removePrintExcep) {
                    removedFileList.push(files);
                }
            });
            removedFileList.forEach(function (removedFile) {
                printExceptionList.forEach(function (printException) {
                    if (removedFile.start === printException.start) {
                        _this.selectedProduct.removePageException(printException.pe);
                    }
                });
            });
        };
        ProductController.prototype.getNewTabsInsertPageRanges = function (pageExceptions) {
            var startRangeList = [];
            var configuredIt = this.product.pageExceptions.iterator();
            var _loop_7 = function () {
                var configuredPE = configuredIt.next();
                pageExceptions.forEach(function (pe) {
                    if (Number(pe.pageExceptionId) === Number(configuredPE.id)) {
                        var pePropIt = configuredPE.properties.iterator();
                        while (pePropIt.hasNext()) {
                            var peProp = pePropIt.next();
                            if (peProp.value === 'TAB' || peProp.value === 'INSERT') {
                                startRangeList.push({ start: pe.pageRanges[0].start, position: pe.pageRanges[0].position });
                            }
                        }
                    }
                });
            };
            while (configuredIt.hasNext()) {
                _loop_7();
            }
            return startRangeList;
        };
        ProductController.prototype.convertToVirtualPages = function (pageRanges, newTabsInsertList) {
            var startRangeList = [];
            if (this.selectedProduct.pageExceptions.size() > 0) {
                var pageExceptionIt = this.selectedProduct.pageExceptions.iterator();
                while (pageExceptionIt.hasNext()) {
                    var pageException = pageExceptionIt.next();
                    var pageExceptionPropIt = pageException.properties.iterator();
                    while (pageExceptionPropIt.hasNext()) {
                        var pageExceptionProp = pageExceptionPropIt.next();
                        if (pageExceptionProp.value === 'TAB' || pageExceptionProp.value === 'INSERT') {
                            var rangeIt = pageException.ranges.iterator();
                            while (rangeIt.hasNext()) {
                                var range = rangeIt.next();
                                startRangeList.push(range.start);
                            }
                        }
                    }
                }
                startRangeList.sort(function (oldRange, newRange) {
                    return oldRange - newRange;
                });
            }
            var count = 0;
            if (pageRanges.position === 'BEFORE') {
                newTabsInsertList.forEach(function (range) {
                    if (range.start < pageRanges.start) {
                        count++;
                    }
                });
            }
            else {
                var lastPagesOnly_1 = true;
                newTabsInsertList.forEach(function (range) {
                    if (range.start <= pageRanges.start && range.position === 'BEFORE') {
                        count++;
                        lastPagesOnly_1 = false;
                    }
                });
                if (lastPagesOnly_1) {
                    count++;
                }
            }
            var virtualStartPage = pageRanges.start + count;
            var fileRanges = this.getAllFilePageRanges();
            var virtualCount = 0;
            var caEndRangeList = [];
            fileRanges.forEach(function (caRanges) {
                virtualCount = 0;
                newTabsInsertList.forEach(function (range) {
                    if (range.start <= caRanges.end && range.position === 'BEFORE') {
                        virtualCount++;
                    }
                });
                caEndRangeList.push(caRanges.end + virtualCount);
            });
            if (startRangeList.length === 0) {
                caEndRangeList.forEach(function (endRange) {
                    if (endRange === virtualStartPage) {
                        virtualStartPage++;
                    }
                });
            }
            else {
                startRangeList.forEach(function (startRange) {
                    if (startRange === virtualStartPage) {
                        virtualStartPage++;
                    }
                    caEndRangeList.forEach(function (endRange) {
                        if (endRange === virtualStartPage) {
                            virtualStartPage++;
                        }
                    });
                });
            }
            return { start: virtualStartPage, end: virtualStartPage };
        };
        ProductController.prototype.addExternalSku = function (sku) {
            this.selectedProduct.addExternalSku(this.productTranslator.translateExternalSku(sku));
            this.selectedProductModified();
        };
        ProductController.prototype.removeExternalSkuCode = function (skuCode) {
            var externalSku = null;
            var index = 0;
            if (skuCode != null) {
                var externalSkus = this.selectedProduct.externalSkus.iterator();
                while (externalSkus.hasNext()) {
                    externalSku = externalSkus.next();
                    if (externalSku.code === skuCode) {
                        this.selectedProduct.externalSkus.remove(index);
                    }
                    index++;
                }
                this.selectedProductModified();
            }
        };
        ProductController.prototype.removeAllExternalSku = function () {
            this.selectedProduct.removeAllExternalSkus();
            this.selectedProductModified();
        };
        ProductController.prototype.removeExternalSkus = function (valueArray) {
            var newFilteredArrayList = new ProductEngine.ArrayList();
            var filteredExternalSkusArray = [];
            if (valueArray != null) {
                filteredExternalSkusArray = this.selectedProduct.externalSkus.toArray().filter(function (el) {
                    return valueArray.some(function (f) {
                        return f.code !== el.code && f.qty !== el.qty;
                    });
                });
                for (var i = 0; i < filteredExternalSkusArray.length; i++) {
                    newFilteredArrayList.add(filteredExternalSkusArray[i]);
                }
                this.selectedProduct.setExternalSkus(newFilteredArrayList);
                this.selectedProductModified();
            }
        };
        ProductController.prototype.setValueDetails = function (valueDetails) {
            for (var i = 0; i < valueDetails.length; i++) {
                var valueDetail = valueDetails[i];
                if (valueDetail.value && valueDetail.valueType) {
                    if (valueDetail.valueType.toUpperCase() === 'WEIGHT') {
                        this.setProductWeight(valueDetail.value, valueDetail.unit);
                    }
                    if (valueDetail.valueType.toUpperCase() === 'TIME') {
                        this.setProductionTime(valueDetail.value, valueDetail.unit);
                    }
                }
            }
            this.selectedProductModified();
        };
        ProductController.prototype.setProductWeight = function (value, units) {
            var weight = new ProductEngine.ExternalProductionWeight();
            if (units !== null) {
                if (units === 'LB') {
                    weight.setUnits(ProductEngine.WeightUnit.LB);
                }
            }
            else {
                weight.setUnits(ProductEngine.WeightUnit.LB);
            }
            if (value !== null) {
                weight.setValue(ProductEngine.Utils.convertToFloat(value));
            }
            if (this.selectedProduct.getExternalProductionDetails() !== null) {
                this.selectedProduct.getExternalProductionDetails().setWeight(weight);
            }
            else {
                var externalProductionDetails = new ProductEngine.ExternalProductionDetails();
                externalProductionDetails.setWeight(weight);
                this.selectedProduct.setExternalProductionDetails(externalProductionDetails);
            }
        };
        ProductController.prototype.setProductionTime = function (value, units) {
            var productionTime = new ProductEngine.ExternalProductionTime();
            if (units !== null) {
                if (units === 'DAY') {
                    productionTime.setUnits(ProductEngine.TimeUnit.DAY);
                }
                if (units === 'HOUR') {
                    productionTime.setUnits(ProductEngine.TimeUnit.HOUR);
                }
            }
            else {
                productionTime.setUnits(ProductEngine.TimeUnit.HOUR);
            }
            if (value !== null) {
                productionTime.setValue(ProductEngine.Utils.convertToFloat(value));
            }
            if (this.selectedProduct.getExternalProductionDetails() !== null) {
                this.selectedProduct.getExternalProductionDetails().setProductionTime(productionTime);
            }
            else {
                var externalProductionDetails = new ProductEngine.ExternalProductionDetails();
                externalProductionDetails.setProductionTime(productionTime);
                this.selectedProduct.setExternalProductionDetails(externalProductionDetails);
            }
        };
        ProductController.prototype.copyUserSiToLog = function () {
            var additionalPrintInstructionsProperty = this.getPropertyByName('USER_SPECIAL_INSTRUCTIONS', this.selectedProduct.properties);
            var customerPrintInstructionsProperty = this.getPropertyByName('CUSTOMER_SI', this.selectedProduct.properties);
            var properties = [];
            if (additionalPrintInstructionsProperty && additionalPrintInstructionsProperty.value !== null && additionalPrintInstructionsProperty.value.trim() !== '') {
                if (customerPrintInstructionsProperty) {
                    if (customerPrintInstructionsProperty.value === null || customerPrintInstructionsProperty.value.trim() === null) {
                        customerPrintInstructionsProperty.value = additionalPrintInstructionsProperty.value.trim();
                    }
                    else {
                        customerPrintInstructionsProperty.value = customerPrintInstructionsProperty.value.trim() + '--' + additionalPrintInstructionsProperty.value.trim();
                    }
                    properties.push(customerPrintInstructionsProperty);
                    additionalPrintInstructionsProperty.value = null;
                    properties.push(additionalPrintInstructionsProperty);
                    this.setPropertyValueByIds(properties, this.selectedProduct);
                }
            }
        };
        ProductController.prototype.setPhysicalContentDetails = function (pageCount) {
            if (pageCount != null) {
                var contentAssociation = null;
                var flattenedProduct = this.getFlattenedProduct();
                var mediaWidthValue = flattenedProduct.properties.MEDIA_WIDTH;
                var mediaHeightValue = flattenedProduct.properties.MEDIA_HEIGHT;
                var contentHint = new ProductEngine.ContentHint();
                var contentHintList = new ProductEngine.ArrayList();
                contentHint.setPageCount(pageCount);
                contentHint.setHeight(mediaHeightValue);
                contentHint.setWidth(mediaWidthValue);
                contentHintList.add(contentHint);
                var contentAssociations = this.productEngine.buildContentAssociation(this.product, this.selectedProduct, contentHintList);
                this.selectedProduct.contentAssociations.clear();
                var contentAssociationsIterator = contentAssociations.iterator();
                while (contentAssociationsIterator.hasNext()) {
                    contentAssociation = contentAssociationsIterator.next();
                    contentAssociation.setPrintReady(false);
                    contentAssociation.setPhysicalContent(true);
                    this.selectedProduct.addContentAssociation(contentAssociation);
                }
                this.selectedProductModified();
            }
        };
        ProductController.prototype.removePhysicalContentDetails = function () {
            var contentAssociations = null;
            var contentAssociationsIterator = this.selectedProduct.contentAssociations.iterator();
            var index = 0;
            while (contentAssociationsIterator.hasNext()) {
                contentAssociations = contentAssociationsIterator.next();
                if (contentAssociations.physicalContent === true) {
                    this.selectedProduct.contentAssociations.remove(index);
                    this.selectedProductModified();
                }
                index = index + 1;
            }
        };
        return ProductController;
    }());
    exports.ProductController = ProductController;
})
