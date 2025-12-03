define(['jquery', 'Magento_Checkout/js/model/step-navigator','fedex/storage'], function ($, stepNavigator,fxoStorage) {

    const PICKUP_INVALID_CODE = "REQUESTED.PICKUPDELIVERYDATE.EARLIER.THAN.ORDERREADYDATE";
    const errorMessages = {
        [PICKUP_INVALID_CODE]: {
            messageTitle:'Pickup location and time no longer available',
            messageDetails: 'Please review your store pickup details and try again',
            ctaLabel: 'SELECT LOCATION AND TIME'
        },
    };

    let keepErrorToastOpen = false;

    /**
     * @param {object} response
     * @returns {string}
     */

    function reloadPickUpComponent(selectedStoreName, isPickupReloadCta = false) {

        $('#search-pickup').trigger('click');
        let pickupLocationListContainer = $(".pickup-location-list-container");
        pickupLocationListContainer.unbind('rendered');

        pickupLocationListContainer.on('rendered', function() {
            $('.pickup-location-container .radio-label span').each(function() {
                if ($(this).text() === selectedStoreName) {
                    $(this).parent().parent().find('input').trigger('click');
                }
            });
        });
        pickupLocationListContainer.unbind('pickup-loaded');
        if(!isPickupReloadCta) {
            pickupLocationListContainer.on('pickup-loaded', function() {
                $('.error-container.checkout-error').removeClass('hide').removeClass('api-error-hide').show();
            });
        }
    }

    function errorHandler(response, isPromoCode) {
        errorToastDisplayHandler();
        if (
            response !== undefined &&
            response !== null &&
            (
                (response.errors && response.errors.length > 0) ||
                (response.errors && response.errors.errors && response.errors.errors.length > 0)
            )
        ) {

            $('.pickup-error-hide').hide();
            let errorMessage = '';
            let responseCode = '';

            isPromoCode ? responseCode = response.errors.errors[0].code : responseCode = response.errors.errors ? response.errors.errors[0].code : response.errors[0].code;

            errorMessage = errorMessages[responseCode];

            if (!errorMessage) {
                return
            }

            $('.error-container.checkout-error').removeClass('hide').removeClass('api-error-hide');

            isPromoCode && $('.promo-code-submit-button span').css('visibility', 'visible');

            $('.error-container .error-title').text(errorMessage.messageTitle);
            $('.error-container .error-details').text(errorMessage.messageDetails);
            $(".error-container .error-transaction").replaceWith(`<a class='checkout-sub reload-pickup no-underline'> ${errorMessage.ctaLabel} </a>`);

            let reloadPickupCta = $('.error-container .reload-pickup');
            reloadPickupCta.toggle(window.location.hash !== "#shipping");
            $('.error-container .img-close-pop').toggle(window.location.hash === "#shipping");

            reloadPickupCta.unbind('click');

            // Reload pickup page on CTA click
            reloadPickupCta.on( "click", function() {
                stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                $(".place-pickup-order").show();
                $('.error-container.checkout-error').addClass('hide').addClass('api-error-hide');
                let pickupDataString, pickupDataObj;
                if (window.e383157Toggle) {
                    pickupDataObj = fxoStorage.get('pickupData');
                } else {
                    pickupDataString = localStorage.getItem('pickupData');
                    pickupDataObj = JSON.parse(pickupDataString);
                }
                let selectedStoreName = pickupDataObj.addressInformation.pickup_location_name;
                reloadPickUpComponent(selectedStoreName, true);
            });

            // If the error occurs when the component is visible, we need to refresh it immediately
            // Also keep the error toast open after the component is refreshed
            if (window.checkoutConfig.is_pickup && window.location.hash === "#shipping") {
                let selectedStoreName = $(".pickup-address.selected-item").parent().find(".radio-label span").text();
                reloadPickUpComponent(selectedStoreName);
                keepErrorToastOpen = true;
            }
        }
    }

    /**
     * Keep the error toast open handler.
     * There are some cases where the error toast should be kept open. For example, when the user is at the pick-up
     * screen and the pick-up component should be reloaded after a rate quote error.
     */
     function errorToastDisplayHandler() {
        if(keepErrorToastOpen) {
            $('.error-container.checkout-error').removeClass('hide').removeClass('api-error-hide');
            keepErrorToastOpen = false;
        }
    }

    return {
        errorHandler: errorHandler
    }
})
