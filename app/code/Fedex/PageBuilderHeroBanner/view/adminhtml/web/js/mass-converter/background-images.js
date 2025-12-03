/*eslint-disable */
/* jscs:disable */
define(["underscore", "Magento_PageBuilder/js/utils/image", "Magento_PageBuilder/js/utils/object"], function (_underscore, _image, _object) {
    /**
     * Copyright © Fedex, Inc. All rights reserved.
     * See COPYING.txt for license details.
     */
    let BackgroundImages =
        /*#__PURE__*/
        function () {
            "use strict";

            function BackgroundImages() { /* TODO document why this function 'BackgroundImages' is empty */ }

            let _proto = BackgroundImages.prototype;

            /**
             * Process data after it's read and converted by element converters
             *
             * @param {ConverterDataInterface} data
             * @param {ConverterConfigInterface} config
             * @returns {object}
             */
            _proto.fromDom = function fromDom(data, config) {
                let directive = (0, _object.get)(data, config.attribute_name);
                let foregroundImage = (0, _object.get)(data, config.foreground_image_toggle_variable);
                let foregroundLImage = (0, _object.get)(data, config.foreground_laptop_image_toggle_variable);
                let foregroundTImage = (0, _object.get)(data, config.foreground_tablet_image_toggle_variable);

                if (directive) {
                    let images = JSON.parse(directive.replace(/\\(.)/mg, "$1")) || {};

                    if (!_underscore.isUndefined(images.desktop_image)) {
                        (0, _object.set)(data, config.desktop_image_variable, (0, _image.decodeUrl)(images.desktop_image));
                    }

                    if (!_underscore.isUndefined(images.mobile_image)) {
                        (0, _object.set)(data, config.mobile_image_variable, (0, _image.decodeUrl)(images.mobile_image));
                    }

                    if (!_underscore.isUndefined(images.desktop_medium_image)) {
                        (0, _object.set)(data, config.desktop_medium_image_variable, (0, _image.decodeUrl)(images.desktop_medium_image));
                    }

                    if (!_underscore.isUndefined(images.mobile_medium_image)) {
                        (0, _object.set)(data, config.mobile_medium_image_variable, (0, _image.decodeUrl)(images.mobile_medium_image));
                    }

                    delete data[config.attribute_name];
                }
                return data;
            }
            /**
             * Process data before it's converted by element converters
             *
             * @param {ConverterDataInterface} data
             * @param {ConverterConfigInterface} config
             * @returns {object}
             */
            ;

            _proto.toDom = function toDom(data, config) {
                let desktopImage = (0, _object.get)(data, config.desktop_image_variable);
                let mobileImage = (0, _object.get)(data, config.mobile_image_variable);
                let dekstopmediumImage = (0, _object.get)(data, config.desktop_medium_image_variable);
                let mobilemediumImage = (0, _object.get)(data, config.mobile_medium_image_variable);

                let directiveData = {};

                if (!_underscore.isUndefined(desktopImage) && desktopImage && !_underscore.isUndefined(desktopImage[0])) {
                    directiveData.desktop_image = (0, _image.urlToDirective)(desktopImage[0].url);
                }

                if (!_underscore.isUndefined(mobileImage) && mobileImage && !_underscore.isUndefined(mobileImage[0])) {
                    directiveData.mobile_image = (0, _image.urlToDirective)(mobileImage[0].url);
                } // Add the directive data, ensuring we escape double quotes

                if (!_underscore.isUndefined(dekstopmediumImage) && dekstopmediumImage && !_underscore.isUndefined(dekstopmediumImage[0])) {
                    directiveData.desktop_medium_image = (0, _image.urlToDirective)(dekstopmediumImage[0].url);
                }

                if (!_underscore.isUndefined(mobilemediumImage) && mobilemediumImage && !_underscore.isUndefined(mobilemediumImage[0])) {
                    directiveData.mobile_medium_image = (0, _image.urlToDirective)(mobilemediumImage[0].url);
                }

                (0, _object.set)(data, config.attribute_name, JSON.stringify(directiveData).replace(/[\\"']/g, "\\$&").replace('/\u0000/g', "\\0"));

                return data;
            };

            return BackgroundImages;
        }();

    return BackgroundImages;
});
//# sourceMappingURL=background-images.js.map
