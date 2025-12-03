define(["require","exports","peProductEngine"], function (require, exports, ProductEngine) {
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var ProductTranslator = (function () {
    function ProductTranslator() {
        this.parser = new ProductEngine.JSONModelParser();
        this.parser.addDefaultOverrides();
        this.parser.addEnumField(ProductEngine.BooleanOperator, ProductEngine.AbstractCondition, 'getBooleanOperator', 'setBooleanOperator');
        this.parser.addEnumField(ProductEngine.ComparisonOperator, ProductEngine.ComparisonCondition, 'getCompOp', 'setCompOp');
        this.parser.addEnumField(ProductEngine.CalcOperator, ProductEngine.CalcValueProvider, 'getOp', 'setOp');
        this.parser.addEnumField(ProductEngine.ValueType, ProductEngine.AbstractValueProvider, 'getType', 'setType');
        this.parser.addEnumField(ProductEngine.CalcOperator, ProductEngine.CalcMultiValueProvider, 'getOp', 'setOp');
        this.parser.addEnumField(ProductEngine.CalcOperator, ProductEngine.CalcMultiConditionalValueProvider, 'getOp', 'setOp');
    }
    ProductTranslator.prototype.elementCopy = function (from, to, dontCopy) {
        if (dontCopy === void 0) { dontCopy = {}; }
        if (from) {
            for (var prop in from) {
                if (!dontCopy.hasOwnProperty(prop) && from.hasOwnProperty(prop)) {
                    to[prop] = from[prop];
                }
            }
        }
    };
    ProductTranslator.prototype.translateProductInstance = function (data) {
        data.id = String(data.id);
        var productInstance = new ProductEngine.ProductInstance();
        productInstance.contentAssociations = new ProductEngine.ArrayList();
        this.elementCopy(data, productInstance, { features: true, contentRequirement: true, contentAssociations: true, properties: true, pageExceptions: true, externalSkus: true, externalProductionDetails: true, contextKeys: true });
        if (ProductEngine.Utils.isEmptyArray(data.properties)) {
            for (var i = 0; i < data.properties.length; i++) {
                productInstance.properties.add(this.translatePropertyInstance(data.properties[i]));
            }
        }
        if (ProductEngine.Utils.isEmptyArray(data.contentAssociations)) {
            var contentAssociationList = new ProductEngine.ArrayList();
            for (var i = 0; i < data.contentAssociations.length; i++) {
                contentAssociationList.add(this.translateContentAssociationInstance(data.contentAssociations[i]));
            }
            productInstance.setContentAssociations(contentAssociationList);
        }
        var prdContentAssociationList = new ProductEngine.ArrayList();
        if (data.productionContentAssociations !== undefined && data.productionContentAssociations !== null) {
            for (var i = 0; i < data.productionContentAssociations.length; i++) {
                prdContentAssociationList.add(this.translateProductionContentAssociation(data.productionContentAssociations[i]));
            }
        }
        productInstance.setProductionContentAssociations(prdContentAssociationList);
        if (ProductEngine.Utils.isEmptyArray(data.features)) {
            for (var i = 0; i < data.features.length; i++) {
                if (data.features[i] !== undefined && data.features[i] !== null && typeof data.features[i] === 'object' && Object.keys(data.features[i]).length !== 0) {
                    productInstance.addFeature(this.translateFeatureInstance(data.features[i]));
                }
            }
        }
        if (data.pageExceptions) {
            for (var i = 0; i < data.pageExceptions.length; i++) {
                productInstance.addPageException(this.translatePageExceptionInstance(data.pageExceptions[i]));
            }
        }
        if (data.externalSkus && data.externalSkus.length !== 0) {
            for (var i = 0; i < data.externalSkus.length; i++) {
                productInstance.addExternalSku(this.translateExternalSku(data.externalSkus[i]));
            }
        }
        if (data.contextKeys && data.contextKeys.length !== 0) {
            for (var i = 0; i < data.contextKeys.length; i++) {
                productInstance.addContextKeys(data.contextKeys[i]);
            }
        }
        if (data.externalProductionDetails) {
            productInstance.setExternalProductionDetails(this.translateExternalProductionDetails(data.externalProductionDetails));
        }
        return productInstance;
    };
    ProductTranslator.prototype.translateProductHint = function (data) {
        var productHint = new ProductEngine.ProductHint();
        this.elementCopy(data, productHint, { content: true, choiceIds: true, type: true });
        var contentHintList = new ProductEngine.ArrayList();
        if (data.contentHints) {
            for (var i = 0; i < data.contentHints.length; i++) {
                contentHintList.add(this.translateContentHint(data.contentHints[i]));
            }
            productHint.setContentHints(contentHintList);
        }
        var choiceIdList = new ProductEngine.ArrayList();
        productHint.choiceIds = new ProductEngine.ArrayList();
        if (data.choiceIds) {
            for (var i = 0; i < data.choiceIds.length; i++) {
                choiceIdList.add(data.choiceIds[i]);
            }
            productHint.setChoiceIds(choiceIdList);
        }
        if (data.sourceProduct) {
            if (typeof data.sourceProduct === 'string') {
                var deserializedSourceProduct = this.translateProductInstance(JSON.parse(data.sourceProduct));
                productHint.setSourceProduct(deserializedSourceProduct);
            }
            else {
                var deserializedSourceProduct = this.translateProductInstance(data.sourceProduct);
                productHint.setSourceProduct(deserializedSourceProduct);
            }
        }
        return productHint;
    };
    ProductTranslator.prototype.translateArrayToList = function (data) {
        var dataList = new ProductEngine.ArrayList();
        if (data) {
            for (var i = 0; i < data.length; i++) {
                dataList.add(data[i]);
            }
        }
        return dataList;
    };
    ProductTranslator.prototype.translateContentHint = function (data) {
        var contentHint = new ProductEngine.ContentHint();
        this.elementCopy(data, contentHint, { type: true, contentAssociation: true });
        contentHint.setContentAssociation(this.translateContentAssociationInstance(contentHint.contentAssociation));
        return contentHint;
    };
    ProductTranslator.prototype.translateFeatureInstance = function (data) {
        data.id = String(data.id);
        var featureInstance = new ProductEngine.FeatureInstance();
        this.elementCopy(data, featureInstance, { choice: true });
        featureInstance.setChoice(this.translateChoiceInstance(data.choice));
        return featureInstance;
    };
    ProductTranslator.prototype.translateChoiceInstance = function (data) {
        data.id = String(data.id);
        var choiceInstance = new ProductEngine.ChoiceInstance();
        this.elementCopy(data, choiceInstance, { $$hashKey: true, properties: true, compatibilityGroups: true });
        if (data.properties) {
            for (var i = 0; i < data.properties.length; i++) {
                choiceInstance.properties.add(this.translatePropertyInstance(data.properties[i]));
            }
        }
        return choiceInstance;
    };
    ProductTranslator.prototype.translatePropertyInstance = function (data) {
        data.id = String(data.id);
        var propertyInstance = new ProductEngine.Property();
        this.elementCopy(data, propertyInstance, { type: true });
        return propertyInstance;
    };
    ProductTranslator.prototype.translateContentAssociationInstance = function (inCa) {
        var outCa = new ProductEngine.ContentAssociation();
        if (inCa.parentContentReference) {
            outCa.setParentContentReference(inCa.parentContentReference);
        }
        if (inCa.contentReference) {
            outCa.setContentReference(String(inCa.contentReference));
        }
        if (inCa.contentReplacementUrl) {
            outCa.setContentReplacementUrl(inCa.contentReplacementUrl);
        }
        if (inCa.contentType) {
            outCa.setContentType(inCa.contentType);
        }
        if (inCa.fileName) {
            outCa.setFileName(inCa.fileName);
        }
        outCa.setContentReqId(inCa.contentReqId);
        if (inCa.name) {
            outCa.setName(inCa.name);
        }
        if (inCa.desc) {
            outCa.setDesc(inCa.desc);
        }
        outCa.setPurpose(inCa.purpose);
        if (inCa.specialInstructions) {
            outCa.setSpecialInstructions(inCa.specialInstructions);
        }
        outCa.setPrintReady(ProductEngine.Utils.convertToBoolean(inCa.printReady));
        if (inCa.physicalContent !== null && inCa.physicalContent !== undefined) {
            outCa.setPhysicalContent(ProductEngine.Utils.convertToBoolean(inCa.physicalContent));
        }
        if (inCa.fileSource && inCa.fileSource !== undefined) {
            outCa.setFileSource(inCa.fileSource);
        }
        if (inCa.pageGroups) {
            var pageGroups = new ProductEngine.ArrayList();
            for (var i = 0; i < inCa.pageGroups.length; i++) {
                pageGroups.add(this.translatePageGroup(inCa.pageGroups[i]));
            }
            outCa.setPageGroups(pageGroups);
        }
        return outCa;
    };
    ProductTranslator.prototype.translateProductionContentAssociation = function (inPrdCa) {
        var outPrdCa = new ProductEngine.ProductionContentAssociation();
        outPrdCa.setParentContentReference(inPrdCa.parentContentReference);
        outPrdCa.setContentReference(inPrdCa.contentReference);
        outPrdCa.setContentState(inPrdCa.contentState);
        outPrdCa.setContentType(inPrdCa.contentType);
        outPrdCa.setPurpose(inPrdCa.purpose);
        outPrdCa.setFileName(inPrdCa.fileName);
        if (inPrdCa.pageGroups) {
            var pageGroups = new ProductEngine.ArrayList();
            for (var i = 0; i < inPrdCa.pageGroups.length; i++) {
                pageGroups.add(this.translatePageGroup(inPrdCa.pageGroups[i]));
            }
            outPrdCa.setPageGroups(pageGroups);
        }
        return outPrdCa;
    };
    ProductTranslator.prototype.translatePageGroup = function (inPage) {
        var outPage = new ProductEngine.PageGroup();
        if (inPage.start) {
            outPage.setStart(ProductEngine.Utils.convertToInteger(inPage.start));
        }
        if (inPage.end) {
            outPage.setEnd(ProductEngine.Utils.convertToInteger(inPage.end));
        }
        if (inPage.width) {
            outPage.setWidth(ProductEngine.Utils.convertToFloat(inPage.width));
        }
        if (inPage.height) {
            outPage.setHeight(ProductEngine.Utils.convertToFloat(inPage.height));
        }
        if (inPage.orientation) {
            outPage.setOrientation(inPage.orientation);
        }
        return outPage;
    };
    ProductTranslator.prototype.translatePageExceptionInstance = function (data) {
        var exception = new ProductEngine.PageExceptionInstance();
        exception.id = data.id;
        exception.hasContent = data.hasContent;
        if (data.ranges) {
            for (var i = 0; i < data.ranges.length; i++) {
                exception.addRange(this.translatePageRange(data.ranges[i]));
            }
        }
        if (data.features) {
            for (var i = 0; i < data.features.length; i++) {
                exception.addFeature(this.translateFeatureInstance(data.features[i]));
            }
        }
        var feProperties = new ProductEngine.ArrayList();
        if (data.properties) {
            for (var i = 0; i < data.properties.length; i++) {
                feProperties.add(this.translatePropertyInstance(data.properties[i]));
            }
        }
        exception.setProperties(feProperties);
        return exception;
    };
    ProductTranslator.prototype.translatePageRange = function (data) {
        var range = new ProductEngine.PageRange();
        range.start = data.start;
        range.end = data.end;
        return range;
    };
    ProductTranslator.prototype.translateDisplayText = function (dataText) {
        var dt = new ProductEngine.DisplayText();
        dt.id = dataText.id;
        dt.text = dataText.text;
        dt.sequence = dataText.sequence;
        dt.characterization = dataText.characterization;
        return dt;
    };
    ProductTranslator.prototype.translateDisplayHint = function (dataHint) {
        var dh = new ProductEngine.DisplayHint();
        dh.id = dataHint.id;
        dh.name = dataHint.name;
        dh.value = dataHint.value;
        return dh;
    };
    ProductTranslator.prototype.translateDisplayDetails = function (data) {
        var dd = new ProductEngine.DisplayDetails();
        dd.id = data.id;
        dd.refId = data.refId;
        dd.name = data.name;
        dd.img = data.img;
        dd.tooltipText = data.tooltipText;
        dd.controlId = data.controlId;
        dd.parentId = data.parentId;
        dd.disabledText = data.disabledText;
        if (data.displayTexts != null) {
            for (var i = 0; i < data.displayTexts.length; i++) {
                dd.displayTexts.add(this.translateDisplayText(data.displayTexts[i]));
            }
        }
        if (data.displayHints != null) {
            for (var i = 0; i < data.displayHints.length; i++) {
                dd.displayHints.add(this.translateDisplayHint(data.displayHints[i]));
            }
        }
        return dd;
    };
    ProductTranslator.prototype.translateProductDisplayDetails = function (data) {
        var dd = new ProductEngine.ProductDisplayDetails();
        dd.id = data.id;
        dd.refId = data.refId;
        dd.name = data.name;
        dd.img = data.img;
        dd.tooltipText = data.tooltipText;
        dd.controlId = data.controlId;
        dd.parentId = data.parentId;
        dd.disabledTexts = data.disabledTexts;
        if (data.displayTexts != null) {
            for (var i = 0; i < data.displayTexts.length; i++) {
                dd.addDisplayText(this.translateDisplayText(data.displayTexts[i]));
            }
        }
        if (data.displayHints != null) {
            for (var i = 0; i < data.displayHints.length; i++) {
                dd.addDisplayHint(this.translateDisplayHint(data.displayHints[i]));
            }
        }
        if (data.controlIds != null) {
            for (var i = 0; i < data.controlIds.length; i++) {
                dd.addControlIds(data.controlIds[i]);
            }
        }
        if (data.displays != null) {
            for (var i = 0; i < data.displays.length; i++) {
                dd.addDisplay(this.translateDisplay(data.displays[i]));
            }
        }
        return dd;
    };
    ProductTranslator.prototype.translateDisplays = function (type, data) {
        if (type === 'SkuDisplay') {
            var dd = new ProductEngine.SkuDisplayDetails();
            dd.id = data.id;
            dd.refId = data.refId;
            if (data.displays && data.displays != null) {
                for (var i = 0; i < data.displays.length; i++) {
                    dd.addDisplay(this.translateDisplay(data.displays[i]));
                }
            }
            return dd;
        }
        if (type === 'ValueDisplay') {
            var dd = new ProductEngine.ValueDisplayDetails();
            dd.id = data.id;
            dd.refId = data.refId;
            dd.valueType = data.valueType;
            if (data.displays && data.displays != null) {
                for (var i = 0; i < data.displays.length; i++) {
                    dd.addDisplay(this.translateDisplay(data.displays[i]));
                }
            }
            return dd;
        }
    };
    ProductTranslator.prototype.translateDisplay = function (data) {
        var dd = new ProductEngine.ElementDisplay();
        if (data.sequence) {
            dd.setSequence(data.sequence);
        }
        else {
            dd.setSequence(-1);
        }
        dd.name = data.name;
        dd.img = data.img;
        dd.tooltipText = data.tooltipText;
        dd.parentId = data.parentId;
        dd.disabledTexts = data.disabledTexts;
        if (data.displayTexts != null) {
            for (var i = 0; i < data.displayTexts.length; i++) {
                dd.addDisplayText(this.translateDisplayText(data.displayTexts[i]));
            }
        }
        if (data.displayHints != null) {
            for (var i = 0; i < data.displayHints.length; i++) {
                dd.addDisplayHint(this.translateDisplayHint(data.displayHints[i]));
            }
        }
        if (data.controlIds != null) {
            for (var i = 0; i < data.controlIds.length; i++) {
                dd.addControlId(data.controlIds[i]);
            }
        }
        return dd;
    };
    ProductTranslator.prototype.translatePropertyInputDetails = function (data) {
        var propertyDisplay = new ProductEngine.PropertyInputDetails();
        propertyDisplay.setId(data.id);
        propertyDisplay.setRefId(data.refId);
        propertyDisplay.setName(data.name);
        propertyDisplay.setParentId(data.parentId);
        propertyDisplay.setControlId(data.controlId);
        if (data.displayHints != null) {
            for (var i = 0; i < data.displayHints.length; i++) {
                propertyDisplay.addDisplayHint(this.translateDisplayHint(data.displayHints[i]));
            }
        }
        if (data.allowedValues != null) {
            propertyDisplay.allowedValues = new ProductEngine.ArrayList();
            for (var i = 0; i < data.allowedValues.length; i++) {
                propertyDisplay.allowedValues.add(this.translateAllowedValue(data.allowedValues[i]));
            }
        }
        if (data.controlIds != null) {
            for (var i = 0; i < data.controlIds.length; i++) {
                propertyDisplay.addControlIds(data.controlIds[i]);
            }
        }
        if (data.displays != null) {
            for (var i = 0; i < data.displays.length; i++) {
                propertyDisplay.addDisplay(this.translatePropertyDisplay(data.displays[i]));
            }
        }
        return propertyDisplay;
    };
    ProductTranslator.prototype.translatePropertyDisplay = function (data) {
        var dd = new ProductEngine.PropertyDisplay();
        if (data.sequence) {
            dd.setSequence(data.sequence);
        }
        else {
            dd.setSequence(-1);
        }
        dd.name = data.name;
        dd.img = data.img;
        dd.tooltipText = data.tooltipText;
        dd.parentId = data.parentId;
        dd.disabledTexts = data.disabledTexts;
        if (data.displayTexts != null) {
            for (var i = 0; i < data.displayTexts.length; i++) {
                dd.addDisplayText(this.translateDisplayText(data.displayTexts[i]));
            }
        }
        if (data.displayHints != null) {
            for (var i = 0; i < data.displayHints.length; i++) {
                dd.addDisplayHint(this.translateDisplayHint(data.displayHints[i]));
            }
        }
        if (data.controlIds != null) {
            for (var i = 0; i < data.controlIds.length; i++) {
                dd.addControlId(data.controlIds[i]);
            }
        }
        if (data.allowedValues != null) {
            dd.allowedValues = new ProductEngine.ArrayList();
            for (var i = 0; i < data.allowedValues.length; i++) {
                dd.allowedValues.add(this.translateAllowedValue(data.allowedValues[i]));
            }
        }
        return dd;
    };
    ProductTranslator.prototype.translateAllowedValue = function (data) {
        var allowedValue = new ProductEngine.PropertyInputDetailsValue();
        if (data.sequence) {
            allowedValue.setSequence(data.sequence);
        }
        else {
            allowedValue.setSequence(-1);
        }
        allowedValue.setName(data.name);
        allowedValue.setValue(data.value);
        return allowedValue;
    };
    ProductTranslator.prototype.translateProductData = function (data, controlId) {
        var productData = new ProductEngine.ConfiguratorProductData();
        if (data != null) {
            productData.product = this.translateProduct(data.product);
            productData.rules = this.translateProductRules(data.rules);
            productData.displays = this.translateProductDisplays(data.displays, controlId);
            productData.setPresets(this.translatePresets(data.presets));
            productData.setDesignTemplates(this.translateDesignTemplates(data.designTemplates));
        }
        return productData;
    };
    ProductTranslator.prototype.translateDesignTemplates = function (inDesignTemplates) {
        var outDesignTemplates = new ProductEngine.ArrayList();
        if (inDesignTemplates != null) {
            for (var i = 0; i < inDesignTemplates.length; i++) {
                outDesignTemplates.add(this.translateDesignTemplate(inDesignTemplates[i]));
            }
        }
        return outDesignTemplates;
    };
    ProductTranslator.prototype.translateDesignTemplate = function (inDesignTemplate) {
        var outDesignTemplate = new ProductEngine.DesignTemplate();
        var outFeatureRefs = new ProductEngine.ArrayList();
        var outTemplates = new ProductEngine.ArrayList();
        outDesignTemplate.setVendorCode(inDesignTemplate.vendorCode);
        outDesignTemplate.setVendorProductId(inDesignTemplate.vendorProductId);
        outDesignTemplate.setVendorProductVersion(inDesignTemplate.vendorProductVersion);
        outDesignTemplate['@type'] = 'DesignTemplate';
        if (inDesignTemplate.templates) {
            for (var i = 0; i < inDesignTemplate.templates.length; i++) {
                outTemplates.add(this.translateTemplateList(inDesignTemplate.templates[i]));
            }
            outDesignTemplate.setTemplates(outTemplates);
        }
        if (inDesignTemplate.featureRefs) {
            for (var i = 0; i < inDesignTemplate.featureRefs.length; i++) {
                outFeatureRefs.add(this.translateFeatureRefsList(inDesignTemplate.featureRefs[i]));
            }
            outDesignTemplate.setFeatureRefs(outFeatureRefs);
        }
        return outDesignTemplate;
    };
    ProductTranslator.prototype.translateFeatureRefsList = function (inFeatureRef) {
        var outFeatureRef = new ProductEngine.FeatureReference();
        var outChoices = new ProductEngine.ArrayList();
        outFeatureRef.setFeatureId(inFeatureRef.featureId);
        outFeatureRef.setDefaultChoiceId(inFeatureRef.defaultChoiceId);
        if (inFeatureRef.choiceIds) {
            for (var i = 0; i < inFeatureRef.choiceIds.length; i++) {
                outChoices.add(inFeatureRef.choiceIds[i]);
            }
            outFeatureRef.setChoiceIds(outChoices);
        }
        outFeatureRef['@type'] = 'FeatureReference';
        return outFeatureRef;
    };
    ProductTranslator.prototype.translateTemplateList = function (template) {
        var outTemp = new ProductEngine.Template();
        var outChoices = new ProductEngine.ArrayList();
        outTemp.setId(template.templateId);
        if (template.choiceIds) {
            for (var i = 0; i < template.choiceIds.length; i++) {
                outChoices.add(template.choiceIds[i]);
            }
            outTemp.setChoiceIds(outChoices);
        }
        outTemp['@type'] = 'Template';
        return outTemp;
    };
    ProductTranslator.prototype.translateProductDisplays = function (data, controlId) {
        var pd = new ProductEngine.ProductDisplays();
        if (data != null) {
            if (data.productDisplays != null) {
                for (var i = 0; i < data.productDisplays.length; i++) {
                    var translatedDisplayData = this.translateProductDisplayDetails(data.productDisplays[i]);
                    if (translatedDisplayData.displays && translatedDisplayData.displays.size() > 0) {
                        var matchedDisplay = null;
                        var defaultDisplay = null;
                        for (var j = 0; j < translatedDisplayData.displays.size(); j++) {
                            var display = translatedDisplayData.displays.get(j);
                            if (display.controlIds.contains(controlId)) {
                                matchedDisplay = display;
                            }
                            else if (display.controlIds === null || display.controlIds.size() === 0) {
                                defaultDisplay = display;
                            }
                        }
                        if (matchedDisplay !== null || defaultDisplay !== null) {
                            if (matchedDisplay !== null) {
                                this.elementCopy(matchedDisplay, translatedDisplayData);
                            }
                            else {
                                this.elementCopy(defaultDisplay, translatedDisplayData);
                            }
                            if (pd.getProductDisplayByRefId(translatedDisplayData.refId) && translatedDisplayData.refId) {
                                pd.removeProductDisplay(pd.getProductDisplayByRefId(translatedDisplayData.refId));
                            }
                            pd.addProductDisplay(translatedDisplayData);
                        }
                    }
                    else {
                        if (pd.getProductDisplayByRefId(translatedDisplayData.refId) && translatedDisplayData.refId) {
                            if (translatedDisplayData.controlIds.contains(controlId) || translatedDisplayData.controlId === controlId) {
                                pd.removeProductDisplay(pd.getProductDisplayByRefId(translatedDisplayData.refId));
                                pd.addProductDisplay(translatedDisplayData);
                            }
                        }
                        else {
                            if (translatedDisplayData.controlId == null || (translatedDisplayData.controlIds.contains(controlId) || translatedDisplayData.controlId === controlId)) {
                                pd.addProductDisplay(translatedDisplayData);
                            }
                        }
                    }
                }
            }
            if (data.propertyDisplays != null) {
                for (var i = 0; i < data.propertyDisplays.length; i++) {
                    var translatedPropertyData = this.translatePropertyInputDetails(data.propertyDisplays[i]);
                    if (translatedPropertyData.displays && translatedPropertyData.displays.size() > 0) {
                        var matchedDisplay = null;
                        var defaultDisplay = null;
                        for (var j = 0; j < translatedPropertyData.displays.size(); j++) {
                            var display = translatedPropertyData.displays.get(j);
                            if (display.controlIds.contains(controlId)) {
                                matchedDisplay = display;
                            }
                            else if (display.controlIds === null || display.controlIds.size() === 0) {
                                defaultDisplay = display;
                            }
                        }
                        if (matchedDisplay !== null || defaultDisplay !== null) {
                            if (matchedDisplay !== null) {
                                this.elementCopy(matchedDisplay, translatedPropertyData);
                            }
                            else {
                                this.elementCopy(defaultDisplay, translatedPropertyData);
                            }
                            if (pd.getPropertyDisplayByRefId(translatedPropertyData.refId)) {
                                pd.removePropertyDisplay(pd.getPropertyDisplayByRefId(translatedPropertyData.refId));
                            }
                            pd.addPropertyDisplay(translatedPropertyData);
                        }
                    }
                    else {
                        if (pd.getPropertyDisplayByRefId(translatedPropertyData.refId)) {
                            if ((translatedPropertyData.controlIds.contains(controlId) || translatedPropertyData.controlId === controlId) && translatedPropertyData.refId) {
                                pd.removePropertyDisplay(pd.getPropertyDisplayByRefId(translatedPropertyData.refId));
                                pd.addPropertyDisplay(translatedPropertyData);
                            }
                        }
                        else {
                            if (translatedPropertyData.controlId == null || (translatedPropertyData.controlIds.contains(controlId) || translatedPropertyData.controlId === controlId)) {
                                pd.addPropertyDisplay(translatedPropertyData);
                            }
                        }
                    }
                }
            }
            if (data.skuDisplays && data.skuDisplays != null) {
                for (var i = 0; i < data.skuDisplays.length; i++) {
                    var translatedSkuDisplayData = this.translateDisplays('SkuDisplay', data.skuDisplays[i]);
                    if (translatedSkuDisplayData.displays && translatedSkuDisplayData.displays.size() > 0) {
                        var matchedDisplay = null;
                        var defaultDisplay = null;
                        for (var j = 0; j < translatedSkuDisplayData.displays.size(); j++) {
                            var display = translatedSkuDisplayData.displays.get(j);
                            if (display.controlIds.contains(controlId)) {
                                matchedDisplay = display;
                            }
                            else if (display.controlIds === null || display.controlIds.size() === 0) {
                                defaultDisplay = display;
                            }
                        }
                        if (matchedDisplay !== null || defaultDisplay !== null) {
                            if (matchedDisplay !== null) {
                                this.elementCopy(matchedDisplay, translatedSkuDisplayData);
                            }
                            else {
                                this.elementCopy(defaultDisplay, translatedSkuDisplayData);
                            }
                            pd.addSkuDisplay(translatedSkuDisplayData);
                        }
                    }
                }
            }
            if (data.valueDisplays && data.valueDisplays != null) {
                for (var i = 0; i < data.valueDisplays.length; i++) {
                    var translatedValueDisplayData = this.translateDisplays('ValueDisplay', data.valueDisplays[i]);
                    if (translatedValueDisplayData.displays && translatedValueDisplayData.displays.size() > 0) {
                        var matchedDisplay = null;
                        var defaultDisplay = null;
                        for (var j = 0; j < translatedValueDisplayData.displays.size(); j++) {
                            var display = translatedValueDisplayData.displays.get(j);
                            if (display.controlIds.contains(controlId)) {
                                matchedDisplay = display;
                            }
                            else if (display.controlIds === null || display.controlIds.size() === 0) {
                                defaultDisplay = display;
                            }
                        }
                        if (matchedDisplay !== null || defaultDisplay !== null) {
                            if (matchedDisplay !== null) {
                                this.elementCopy(matchedDisplay, translatedValueDisplayData);
                            }
                            else {
                                this.elementCopy(defaultDisplay, translatedValueDisplayData);
                            }
                            pd.addValueDisplay(translatedValueDisplayData);
                        }
                    }
                }
            }
        }
        return pd;
    };
    ProductTranslator.prototype.translateProductRules = function (data) {
        var pr = new ProductEngine.ProductRules();
        var rule = null;
        if (data != null && data.rules != null) {
            for (var i = 0; i < data.rules.length; i++) {
                rule = data.rules[i];
                rule['@type'] = 'Rule';
                pr.addRule(this.parser.translate(rule));
            }
        }
        return pr;
    };
    ProductTranslator.prototype.translateProduct = function (po) {
        var product = new ProductEngine.Product();
        product.setId(po.id);
        product.setVersion(po.version);
        product.setName(po.name);
        product.setQty(po.qty);
        product.setPriceable(po.priceable);
        product.setProofRequired(po.proofRequired);
        product['@type'] = 'Product';
        if (ProductEngine.Utils.isEmptyArray(po.features)) {
            for (var i = 0; i < po.features.length; i++) {
                product.features.add(this.translateFeature(po.features[i]));
            }
        }
        if (ProductEngine.Utils.isEmptyArray(po.properties)) {
            if (po.properties) {
                for (var i = 0; i < po.properties.length; i++) {
                    product.properties.add(this.translateProperty(po.properties[i]));
                }
            }
        }
        if (po.contentRequirements) {
            for (var i = 0; i < po.contentRequirements.length; i++) {
                product.contentRequirements.add(this.translateContentRequirements(po.contentRequirements[i]));
            }
        }
        if (po.pageExceptions) {
            for (var i = 0; i < po.pageExceptions.length; i++) {
                product.pageExceptions.add(this.translatePageException(po.pageExceptions[i]));
            }
        }
        if (po.externalRequirements) {
            product.setExternalRequirements(this.translateExternalRequirements(po.externalRequirements));
        }
        return product;
    };
    ProductTranslator.prototype.translateFeature = function (fo) {
        var feature = new ProductEngine.Feature();
        feature.setId(fo.id);
        feature.setName(fo.name);
        feature.setChoiceRequired(fo.choiceRequired);
        feature.setDefaultChoiceId(fo.defaultChoiceId);
        feature.setOverrideWithDefault(fo.overrideWithDefault);
        feature['@type'] = 'Feature';
        for (var i = 0; i < fo.choices.length; i++) {
            feature.choices.add(this.translateChoice(fo.choices[i]));
        }
        return feature;
    };
    ProductTranslator.prototype.translateChoice = function (co) {
        var choice = new ProductEngine.Choice();
        choice.setId(co.id);
        choice.setName(co.name);
        choice['@type'] = 'Choice';
        if (co.properties) {
            for (var i = 0; i < co.properties.length; i++) {
                choice.properties.add(this.translateProperty(co.properties[i]));
            }
        }
        if (co.compatibilityGroups) {
            for (var i = 0; i < co.compatibilityGroups.length; i++) {
                choice.compatibilityGroups.add(this.translateGroup(co.compatibilityGroups[i]));
            }
        }
        return choice;
    };
    ProductTranslator.prototype.translateProperty = function (inProp) {
        var outProp = new ProductEngine.Property();
        outProp['@type'] = 'Property';
        outProp.setId(inProp.id);
        outProp.setName(inProp.name);
        outProp.setValue(inProp.value);
        outProp.setRequired(inProp.required);
        outProp.setInputAllowed(inProp.inputAllowed);
        if (inProp.bound) {
            outProp.setBound(this.translateBound(inProp.bound));
        }
        if (inProp.bounds) {
            for (var i = 0; i < inProp.bounds.length; i++) {
                outProp.bounds.add(this.translateBound(inProp.bounds[i]));
            }
        }
        return outProp;
    };
    ProductTranslator.prototype.translateBound = function (inBound) {
        var outBound = new ProductEngine.Bound();
        outBound['@type'] = 'Bound';
        outBound.setId(inBound.id);
        outBound.setName(inBound.name);
        outBound.setMeasure(inBound.measure);
        outBound.setMin(inBound.min);
        outBound.setMax(inBound.max);
        outBound.setType(inBound.type);
        if (inBound.allowedValues) {
            for (var i = 0; i < inBound.allowedValues.length; i++) {
                outBound.allowedValues.add(this.translatePropertyAllowedValues(inBound.allowedValues[i]));
            }
        }
        outBound.setExpression(inBound.getExpression);
        return outBound;
    };
    ProductTranslator.prototype.translatePropertyAllowedValues = function (inAllowedValues) {
        var outAllowedValues = new ProductEngine.PropertyAllowedValue();
        outAllowedValues['@type'] = 'PropertyAllowedValue';
        outAllowedValues.setId(inAllowedValues.id);
        outAllowedValues.setName(inAllowedValues.name);
        return outAllowedValues;
    };
    ProductTranslator.prototype.translateGroup = function (inGroup) {
        var outGroup = new ProductEngine.CompatibilityGroup();
        outGroup['@type'] = 'CompatibilityGroup';
        outGroup.setId(inGroup.id);
        outGroup.setName(inGroup.name);
        if (inGroup.compatibilitySubGroups) {
            for (var i = 0; i < inGroup.compatibilitySubGroups.length; i++) {
                outGroup.compatibilitySubGroups.add(this.translateSubgroup(inGroup.compatibilitySubGroups[i]));
            }
        }
        return outGroup;
    };
    ProductTranslator.prototype.translateSubgroup = function (inSubgroup) {
        var outSubgroup = new ProductEngine.CompatibilitySubGroup();
        outSubgroup['@type'] = 'CompatibilitySubGroup';
        outSubgroup.setId(inSubgroup.id);
        outSubgroup.setName(inSubgroup.name);
        return outSubgroup;
    };
    ProductTranslator.prototype.translateContentRequirements = function (inContent) {
        var outContent = new ProductEngine.ContentRequirement();
        outContent['@type'] = 'ContentRequirement';
        outContent.setId(inContent.id);
        outContent.setName(inContent.name);
        outContent.setPurpose(inContent.purpose);
        outContent.setAllowMixedOrientation(inContent.allowMixedOrientation);
        outContent.setAllowMixedSize(inContent.allowMixedSize);
        outContent.setResizeIfDefault(inContent.resizeIfDefault);
        outContent.setMinPages(inContent.minPages);
        outContent.setMaxPages(inContent.maxPages);
        outContent.setMaxFiles(inContent.maxFiles);
        outContent.setRequiresPrintReady(inContent.requiresPrintReady);
        outContent.setContentGroup(inContent.contentGroup);
        outContent.allowedSizes = new ProductEngine.ArrayList();
        if (inContent.allowedSizes) {
            for (var i = 0; i < inContent.allowedSizes.length; i++) {
                outContent.allowedSizes.add(this.translateContentDimensions(inContent.allowedSizes[i]));
            }
        }
        if (inContent.bleedDimension) {
            outContent.setBleedDimension(this.translateBleedDimensions(inContent.bleedDimension));
        }
        return outContent;
    };
    ProductTranslator.prototype.translateContentDimensions = function (inSizes) {
        var outSizes = new ProductEngine.ContentDimensions();
        outSizes.setWidth(inSizes.width);
        outSizes.setHeight(inSizes.height);
        return outSizes;
    };
    ProductTranslator.prototype.translateBleedDimensions = function (inBleedDim) {
        var outWidth = new ProductEngine.BleedRange();
        outWidth.setStart(inBleedDim.width.start);
        outWidth.setEnd(inBleedDim.width.end);
        var outHeight = new ProductEngine.BleedRange();
        outHeight.setStart(inBleedDim.height.start);
        outHeight.setEnd(inBleedDim.height.end);
        var outBleedDim = new ProductEngine.BleedDimension();
        outBleedDim.setWidth(outWidth);
        outBleedDim.setHeight(outHeight);
        return outBleedDim;
    };
    ProductTranslator.prototype.translatePageException = function (inFe) {
        var outFe = new ProductEngine.PageException();
        outFe['@type'] = 'PageException';
        outFe.id = inFe.id;
        outFe.name = inFe.name;
        outFe.required = inFe.required;
        if (inFe.features) {
            for (var i = 0; i < inFe.features.length; i++) {
                outFe.features.add(this.translateFeature(inFe.features[i]));
            }
        }
        if (inFe.properties) {
            for (var i = 0; i < inFe.properties.length; i++) {
                outFe.properties.add(this.translateProperty(inFe.properties[i]));
            }
        }
        if (inFe.featureRefs) {
            for (var i = 0; i < inFe.featureRefs.length; i++) {
                outFe.featureRefs.add(this.translateFeatureRefs(inFe.featureRefs[i]));
            }
        }
        return outFe;
    };
    ProductTranslator.prototype.translateFeatureRefs = function (inFeatureRefs) {
        var outFeatureRef = new ProductEngine.FeatureReference();
        outFeatureRef.featureId = inFeatureRefs.featureId;
        outFeatureRef.defaultChoiceId = inFeatureRefs.defaultChoiceId;
        for (var i = 0; i < inFeatureRefs.choiceIds.length; i++) {
            outFeatureRef.choiceIds.add(inFeatureRefs.choiceIds[i]);
        }
        return outFeatureRef;
    };
    ProductTranslator.prototype.translatePresets = function (inPresets) {
        var outPresets = new ProductEngine.ArraySet();
        if (inPresets != null) {
            for (var i = 0; i < inPresets.length; i++) {
                outPresets.add(this.translatePreset(inPresets[i]));
            }
        }
        return outPresets;
    };
    ProductTranslator.prototype.translatePreset = function (inPreset) {
        var outPreset = new ProductEngine.Preset();
        var outChoices = new ProductEngine.ArraySet();
        outPreset.setId(inPreset.id);
        outPreset.setName(inPreset.name);
        outPreset.setSequence(inPreset.sequence);
        outPreset.setQty(inPreset.qty);
        outPreset['@type'] = 'Preset';
        if (inPreset.choices) {
            for (var i = 0; i < inPreset.choices.length; i++) {
                outChoices.add(this.translatePresetChoice(inPreset.choices[i]));
            }
            outPreset.setChoices(outChoices);
        }
        return outPreset;
    };
    ProductTranslator.prototype.translatePresetChoice = function (inPresetChoice) {
        var outPresetChoice = new ProductEngine.PresetChoice();
        outPresetChoice.setId(inPresetChoice.id);
        outPresetChoice.setSelect(inPresetChoice.select);
        outPresetChoice['@type'] = 'PresetChoice';
        return outPresetChoice;
    };
    ProductTranslator.prototype.serializeProductInstance = function (productIn, supportsPropMap) {
        var productOut = {};
        var inProperty = {};
        this.elementCopy(productIn, productOut, { featureIdMap: true, features: true, properties: true, contentRequirements: true, contentAssociations: true, category: true, pageExceptions: true, products: true, externalSkus: true, externalProductionDetails: true });
        productOut.features = [];
        var iterator = productIn.features.iterator();
        while (iterator.hasNext()) {
            productOut.features.push(this.serializeFeatureInstance(iterator.next(), supportsPropMap));
        }
        productOut.pageExceptions = [];
        iterator = productIn.pageExceptions.iterator();
        while (iterator.hasNext()) {
            productOut.pageExceptions.push(this.serializePageExceptionInstance(iterator.next(), supportsPropMap));
        }
        productOut.contentAssociations = [];
        if (productIn.contentAssociations) {
            iterator = productIn.contentAssociations.iterator();
            while (iterator.hasNext()) {
                productOut.contentAssociations.push(this.serializeContentAssociationInstance(iterator.next()));
            }
        }
        productOut.productionContentAssociations = [];
        if (productIn.productionContentAssociations) {
            iterator = productIn.productionContentAssociations.iterator();
            while (iterator.hasNext()) {
                productOut.productionContentAssociations.push(this.serializeProductionContentAssociations(iterator.next()));
            }
        }
        iterator = productIn.properties.iterator();
        productOut.properties = [];
        while (iterator.hasNext()) {
            inProperty = iterator.next();
            productOut.properties.push(this.serializePropertyInstance(inProperty));
        }
        if (productIn.externalSkus) {
            productOut.externalSkus = [];
            iterator = productIn.externalSkus.iterator();
            while (iterator.hasNext()) {
                productOut.externalSkus.push(this.serializeExternalSku(iterator.next()));
            }
        }
        if (productIn.externalProductionDetails !== null) {
            productOut.externalProductionDetails = {};
            if (productIn.externalProductionDetails.weight !== null) {
                productOut.externalProductionDetails.weight = {};
                productOut.externalProductionDetails.weight.value = productIn.externalProductionDetails.weight.value;
                productOut.externalProductionDetails.weight.units = productIn.externalProductionDetails.weight.units.name();
            }
            if (productIn.externalProductionDetails.productionTime !== null) {
                productOut.externalProductionDetails.productionTime = {};
                productOut.externalProductionDetails.productionTime.value = productIn.externalProductionDetails.productionTime.value;
                productOut.externalProductionDetails.productionTime.units = productIn.externalProductionDetails.productionTime.units.name();
            }
        }
        if (productIn.contextKeys) {
            productOut.contextKeys = [];
            iterator = productIn.contextKeys.iterator();
            while (iterator.hasNext()) {
                productOut.contextKeys.push(iterator.next());
            }
        }
        return productOut;
    };
    ProductTranslator.prototype.serializePageExceptionInstance = function (exceptionsIn, supportsPropMap) {
        if (supportsPropMap === void 0) { supportsPropMap = false; }
        var exceptionsOut = {};
        var prop = {};
        this.elementCopy(exceptionsIn, exceptionsOut, { features: true, properties: true, featureIdMap: true, ranges: true });
        var pageRange = exceptionsIn.ranges.iterator();
        exceptionsOut.ranges = [];
        while (pageRange.hasNext()) {
            exceptionsOut.ranges.push(this.serializePageRange(pageRange.next()));
        }
        var iterator = exceptionsIn.features.iterator();
        exceptionsOut.features = [];
        while (iterator.hasNext()) {
            exceptionsOut.features.push(this.serializeFeatureInstance(iterator.next(), supportsPropMap));
        }
        if (exceptionsIn.properties) {
            exceptionsOut.properties = [];
            var propsIterator = exceptionsIn.properties.iterator();
            while (propsIterator.hasNext()) {
                prop = propsIterator.next();
                exceptionsOut.properties.push(this.serializePropertyInstance(prop));
            }
        }
        return exceptionsOut;
    };
    ProductTranslator.prototype.serializePageRange = function (pageRangeIn) {
        var pageRangeOut = {};
        pageRangeOut.start = pageRangeIn.start;
        pageRangeOut.end = pageRangeIn.end;
        return pageRangeOut;
    };
    ProductTranslator.prototype.serializeFeatureInstance = function (featureIn, supportsPropMap) {
        var featureOut = {};
        this.elementCopy(featureIn, featureOut, { $$hashKey: true, choiceIdMap: true, choices: true });
        featureOut.choice = {};
        featureOut.choice = this.serializeChoiceInstance(featureIn.choice, supportsPropMap);
        return featureOut;
    };
    ProductTranslator.prototype.serializeChoiceInstance = function (choiceIn, supportsPropMap) {
        var outChoice = {};
        var prop = {};
        this.elementCopy(choiceIn, outChoice, { $$hashKey: true, properties: true, compatibilityGroups: true });
        if (choiceIn.properties) {
            outChoice.properties = [];
            var iterator = choiceIn.properties.iterator();
            while (iterator.hasNext()) {
                prop = iterator.next();
                outChoice.properties.push(this.serializePropertyInstance(prop));
            }
        }
        return outChoice;
    };
    ProductTranslator.prototype.serializePropertyInstance = function (inProp) {
        var outProp = {};
        outProp.id = inProp.id;
        outProp.name = inProp.name;
        outProp.value = inProp.value;
        return outProp;
    };
    ProductTranslator.prototype.serializeContentAssociationInstance = function (inCont) {
        var outCont = {};
        if (inCont == null) {
            return null;
        }
        this.elementCopy(inCont, outCont, { pageGroups: true });
        outCont.pageGroups = [];
        var iterator = inCont.pageGroups.iterator();
        while (iterator.hasNext()) {
            outCont.pageGroups.push(this.serializePageGroupInstance(iterator.next()));
        }
        return outCont;
    };
    ProductTranslator.prototype.serializeProductionContentAssociations = function (productionContentAssociations) {
        var outPrdContent = {};
        if (productionContentAssociations == null) {
            return null;
        }
        this.elementCopy(productionContentAssociations, outPrdContent, { pageGroups: true });
        outPrdContent.pageGroups = [];
        var iterator = productionContentAssociations.pageGroups.iterator();
        while (iterator.hasNext()) {
            outPrdContent.pageGroups.push(this.serializePageGroupInstance(iterator.next()));
        }
        return outPrdContent;
    };
    ProductTranslator.prototype.serializePageGroupInstance = function (pageGroup) {
        var outPage = {};
        this.elementCopy(pageGroup, outPage, { orientation: true });
        if (pageGroup.orientation) {
            outPage.orientation = pageGroup.orientation.toString();
        }
        return outPage;
    };
    ProductTranslator.prototype.serializeValidationResults = function (validationResults) {
        var results = [];
        var iterator = validationResults.iterator();
        while (iterator.hasNext()) {
            results.push(this.serializeValidationResult(iterator.next()));
        }
        return results;
    };
    ProductTranslator.prototype.serializeValidationResult = function (validationResult) {
        var result = {};
        this.elementCopy(validationResult, result, {
            severity: true, type: true, elementType: true, refIds: true,
            pageGroups: true
        });
        result.severity = validationResult.severity.name();
        if (validationResult.elementType) {
            result.elementType = validationResult.elementType.name();
        }
        if (validationResult.refIds) {
            result.refIds = validationResult.refIds.toArray();
        }
        if (result.pageGroups) {
            result.pageGroups = [];
            var iterator = validationResult.pageGroups.iterator();
            while (iterator.hasNext()) {
                result.pageGroups.push(this.serializePageGroupInstance(iterator.next()));
            }
        }
        return result;
    };
    ProductTranslator.prototype.translateExternalSku = function (insku) {
        var externalSku = new ProductEngine.ExternalSku();
        externalSku.setCode(insku.code);
        externalSku.setQty(insku.qty);
        externalSku.setSkuDescription(insku.skuDescription);
        externalSku.setUnitPrice(insku.unitPrice);
        externalSku.setPrice(insku.price);
        if (insku.applyProductQty) {
            externalSku.setApplyProductQty(insku.applyProductQty);
        }
        return externalSku;
    };
    ProductTranslator.prototype.serializeExternalSku = function (externalSkuIn) {
        var externalSkuOut = {};
        externalSkuOut.code = externalSkuIn.code;
        externalSkuOut.qty = externalSkuIn.qty;
        externalSkuOut.skuDescription = externalSkuIn.skuDescription;
        externalSkuOut.unitPrice = externalSkuIn.unitPrice;
        externalSkuOut.price = externalSkuIn.price;
        if (externalSkuIn.applyProductQty) {
            externalSkuOut.applyProductQty = externalSkuIn.applyProductQty;
        }
        return externalSkuOut;
    };
    ProductTranslator.prototype.translateExternalProductionDetails = function (inExternalProductionDetails) {
        var externalProductionDetails = new ProductEngine.ExternalProductionDetails();
        var weight = new ProductEngine.ExternalProductionWeight();
        var productionTime = new ProductEngine.ExternalProductionTime();
        if (inExternalProductionDetails.weight) {
            var inWeight = inExternalProductionDetails.weight;
            if (inWeight.units !== undefined) {
                if (inWeight.units === 'LB') {
                    weight.setUnits(ProductEngine.WeightUnit.LB);
                }
            }
            if (inWeight.value !== undefined) {
                weight.setValue(ProductEngine.Utils.convertToFloat(inWeight.value));
            }
            externalProductionDetails.setWeight(weight);
        }
        if (inExternalProductionDetails.productionTime) {
            var inProductionTime = inExternalProductionDetails.productionTime;
            if (inProductionTime.units !== undefined) {
                if (inProductionTime.units === 'DAY') {
                    productionTime.setUnits(ProductEngine.TimeUnit.DAY);
                }
                if (inProductionTime.units === 'HOUR') {
                    productionTime.setUnits(ProductEngine.TimeUnit.HOUR);
                }
            }
            if (inProductionTime.value !== undefined) {
                productionTime.setValue(ProductEngine.Utils.convertToFloat(inProductionTime.value));
            }
            externalProductionDetails.setProductionTime(productionTime);
        }
        return externalProductionDetails;
    };
    ProductTranslator.prototype.translateExternalRequirements = function (externalRequirements) {
        if (externalRequirements.weightRequired !== null && externalRequirements.productionTimeRequired !== null) {
            var externalRequirementsObj = new ProductEngine.ExternalRequirements();
            externalRequirementsObj.setWeightRequired(externalRequirements.weightRequired);
            externalRequirementsObj.setProductionTimeRequired(externalRequirements.productionTimeRequired);
            return externalRequirementsObj;
        }
    };
    return ProductTranslator;
}());
exports.ProductTranslator = ProductTranslator;
});
