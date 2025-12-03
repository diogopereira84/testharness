define([
    'jquery',
    'checkout-common',
    'fedex/storage'
], function($, checkoutCommon,fxoStorage) {
    'use strict';

    /**
     * Select shipping option
     */
    return  {
        selectedDeliveryOptionChecked: function () {
                let selectedShippingMethodsRadio = this.checkSelectedShippingMethodsRadio();
                let hasAllPreviousDeliveryMethodsSelected = selectedShippingMethodsRadio.hasAllPreviousDeliveryMethodsSelected;
                let selectedShippingId = selectedShippingMethodsRadio.selectedShippingId;

                if (hasAllPreviousDeliveryMethodsSelected) {
                    selectedShippingId.forEach(shippingId => {
                        if($('input[value="'+shippingId+'"]').length > 1) {
                            $('input[value="'+shippingId+'"]')[1].prop("checked", true);
                            $('input[value="'+shippingId+'"]')[1].trigger("click");
                        } else {
                            $('input[value="'+shippingId+'"]').prop("checked", true);
                            $('input[value="'+shippingId+'"]').trigger("click");
                        }
                    });
                }
        },
        checkShippingOptionId:  function (event) {
            try {
                return $(event.currentTarget.cells[0]).children('input').val();
            } catch(error) {
                return '';
            }
        },

        /**
         * Allow City Characters i.e. Alphabets, Numbers, Single Quotes, Hyphen and Spaces.
         *
         * @param {String|null} cityValue
         * @return {String|null}
         */
        allowCityCharacters: function (cityValue) {
            if (typeof (cityValue) != 'undefined' && cityValue != null) {
                return cityValue.replace(/[^A-Za-z0-9-' \d]/gi, '');
            }
            return cityValue;
        },

        checkSelectedShippingMethodsRadio: function () {
            let selectedShippingMethods;
            if(window.e383157Toggle){
                selectedShippingMethods = fxoStorage.get('selectedShippingMethods') ?? [];
            }else{
                selectedShippingMethods = localStorage.getItem('selectedShippingMethods')
                    ? JSON.parse(localStorage.getItem('selectedShippingMethods'))
                    : [];
            }
            let selectedShippingId = selectedShippingMethods.map(shipping => {
                if (shipping.carrier_code === 'fedexshipping') {
                    return {
                        elementValue: shipping.carrier_code + '_' + shipping.method_code,
                        shippingMethod: shipping
                    };
                }

                return {
                    elementValue: shipping.method_code,
                    shippingMethod: shipping
                };
            });

            let hasAllPreviousDeliveryMethodsSelected = true;

            let missingShippingMethods = [];

            if (selectedShippingId.length > 0) {
                missingShippingMethods = selectedShippingId.map(shippingId => {
                    if (!$('input[value="' + shippingId.elementValue + '"]').length) {
                        hasAllPreviousDeliveryMethodsSelected = false;
                    }

                    return shippingId.shippingMethod;
                });
            }
            else {
                hasAllPreviousDeliveryMethodsSelected = false;
            }

            if (missingShippingMethods.length > 0) {
                selectedShippingMethods = selectedShippingMethods.filter(shippingMethod => !missingShippingMethods.includes(shippingMethod));
                if(window.e383157Toggle){
                    fxoStorage.set('selectedShippingMethods', selectedShippingMethods);
                }else{
                    localStorage.setItem('selectedShippingMethods', JSON.stringify(selectedShippingMethods));
                }
                window.dispatchEvent(new Event('shipping_method'));
            }

            selectedShippingId = selectedShippingId.map(shippingId => shippingId.elementValue);

            return {
                hasAllPreviousDeliveryMethodsSelected,
                selectedShippingId
            };
        },

    };
});
