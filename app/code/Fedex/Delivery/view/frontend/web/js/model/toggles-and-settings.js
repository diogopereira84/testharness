define(['domReady!'], function () {
    'use strict';

    const isCheckoutConfigAvailable = window.checkoutConfig;
    const mediaUrl = window.checkoutConfig?.media_url;
    const checkIcon = mediaUrl + "/check-icon.png";
    const crossIcon = mediaUrl + "/close-button.png";
    const infoUrl = mediaUrl + "/information.png";
    const crossUrl = mediaUrl + "/circle-times.png";
    const locationIcon = mediaUrl + "/location.jpg";
    const hcoinfoIcon = mediaUrl + "/hco-info-image.png";
    const alertIcon = mediaUrl + "/Alert.png";
    const calenderIcon = mediaUrl + "/calender.png";
    const mapMarkerIcon = mediaUrl + "/location-icon.png";
    const limitedTimeIcon = require.toUrl('images/limited_time.png');
    const productImageData = window.checkoutConfig?.product_image_data;
    const isOutSourced = window.checkoutConfig?.is_out_sourced;
    const mixedCart = window.checkoutConfig?.both;
    const pickShowVal = window.checkoutConfig?.is_pickup;
    const shipShowVal = window.checkoutConfig?.is_delivery;
    const customBillingFieldsToggleOn = window.checkoutConfig?.enable_custom_billing_fields;
    const customBillingShipping = window.checkoutConfig?.custom_billing_shipping;
    const isSde = window.checkoutConfig?.is_sde;
    const isSdeStore = window.checkoutConfig?.is_sde_store;
    const profileSession = window.checkoutConfig?.retail_profile_session;
    const restrictedProductionLocation = window.checkoutConfig?.restricted_production_location;
    const fclCustomerDefaultShippingData = window.checkoutConfig?.fcl_customer_default_shipping_data;
    const isEpro = window.checkoutConfig?.is_epro;
    const isShippingAccEditable = window.checkoutConfig?.shipping_account_number_editable;
    const isProductionLocation = window.checkoutConfig?.is_production_location;
    const isCommercial = window.checkoutConfig?.is_commercial;
    const isCovidPeakSeason = window.checkoutConfig?.is_covidPeakSeason;
    const eproPickupAutofill = window.checkoutConfig?.epro_pickup_autofill;
    const fclFirstName = window.checkoutConfig?.fcl_login_customer_detail.first_name;
    const fclLastName = window.checkoutConfig?.fcl_login_customer_detail.last_name;
    const fclContactNumber = window.checkoutConfig?.fcl_login_customer_detail.contact_number;
    const fclExtNumber = window.checkoutConfig?.fcl_login_customer_detail.contact_ext;
    const fclEmailAddress = window.checkoutConfig?.fcl_login_customer_detail.email_address;
    const hcoPriceUpdate = window.checkoutConfig?.hco_price_update;
    const fclShippingDataStatus = window.checkoutConfig?.fcl_customer_default_shipping_data.status;
    const nameErrorMessage = window.checkoutConfig?.input_name_error_message;
    const sdeSignatureMessage = window.checkoutConfig?.sde_signature_message;
    const shippingAccountMsg = window.checkoutConfig?.shippingAccountMessage;
    const toastTitle = window.checkoutConfig?.toastTitle;
    const toastPickupContent = window.checkoutConfig?.toastPickupContent;
    const toastShippingContent = window.checkoutConfig?.toastShippingContent;
    const checkoutDeliveryMethodsTooltip = window.checkoutConfig?.checkoutDeliveryMethodsTooltip;
    const fixShippingResults = window.checkoutConfig?.fix_shipping_results;
    const isLoggedIn = window.checkoutConfig?.is_logged_in;
    const explorersD169768Fix = window.checkoutConfig?.explorers_d_169768_fix
    const explorersRestrictedAndRecommendedProduction = window.checkoutConfig?.explorers_restricted_and_recommended_production
    const isFclCustomer = window.checkoutConfig?.is_fcl_customer;
    const isSelfRegCustomer = window.checkoutConfig?.is_selfreg_customer;
    const saveOrderBeforePayment = window.checkoutConfig?.save_order_before_payment;
    const callRateApiShippingAccountValidation = window.checkoutConfig?.armada_call_rate_api_shipping_validation;
    const isShippingFormLoadBlankFixToggle = window.checkoutConfig?.explorers_shipping_form_load_blank_fix;
    const isExplorersApiErrorHandling = window.checkoutConfig?.explorers_api_error_handling;
    const xmenEditPickupReview = window.checkoutConfig?.xmen_edit_pickup_review;
    const isUploadToQuote = window.checkoutConfig?.isUploadToQuote;
    const isExplorersD146371HideShippingFormFieldsFix = window.checkoutConfig?.explorers_d146371_hide_shipping_form_fields;
    const isExplorersAllowCityFieldCharactersFix = window.checkoutConfig?.explorers_allow_city_field_characters;
    const warningsHandling = window.checkoutConfig?.warnings_handling;
    const discountBreakdown = window.checkoutConfig?.is_discount_breakdown;
    const isCheckoutQuotePriceDashable = window.checkoutConfig?.is_quote_price_is_dashable;
    const pickupSearchErrorDescription = window.checkoutConfig?.pickup_search_error_description;
    const xmenOrderApprovalB2b = window.checkoutConfig?.xmen_order_approval_b2b_enabled;
    const explorersD174773Fix = window.checkoutConfig?.explorers_d_174773_fix;
    const isRecipientAddressEnable = window.checkoutConfig?.is_recipient_address_from_po;
    const isCustomerShippingAccount3PEnabled = window.checkoutConfig?.isCustomerShippingAccount3PEnabled;
    const tigerE424573OptimizingProductCards = window.tigerE424573OptimizingProductCards;
    const isEproUploadtoQuoteToggle = window.checkoutConfig?.explorers_epro_upload_to_quote;
    const isExpectedDeliveryDateEnabled = window?.checkoutConfig?.isExpectedDeliveryDateEnabled;
    const isPriorityPrintLimitedTimeToggle = window.checkoutConfig?.sgc_priority_print_limited_time_tag;
    const isPromiseTimePickupOptionsToggle = window.checkoutConfig?.sgc_promise_time_pickup_options;
    const tiger_D195836FixLoadTimeHeroBanner = window?.tiger_D195836FixLoadTimeHeroBanner;
    const isFuseBidding = window.checkoutConfig?.is_fusebid_toggle_enabled;
    const tiger_e486666 = window.checkoutConfig?.tiger_e486666;
    const isB2532564ToggleEnable = window.checkoutConfig?.tiger_b2532564;
    const tiger_team_D_225000 = window.checkoutConfig?.tiger_team_D_225000;
    const tiger_team_D_227679 = window.checkoutConfig?.tiger_team_D_227679;
    const isCustomerAcknowledgementThirdPartyEnabled = window.checkoutConfig?.isCustomerAcknowledgementThirdPartyEnabled;
    const tiger_e468338 = window.checkoutConfig?.tiger_e468338 || window.checkout?.tiger_e468338 ? true : false;
    const mazegeeksE482379AllowCustomerToChooseProductionLocationUpdates = window.checkoutConfig?.mazegeeks_e_482379_allow_customer_to_choose_production_location_updates;
    const sgc_D_236651 = window.checkoutConfig?.sgc_D_236651;

    return {
        checkIcon: checkIcon,
        crossIcon: crossIcon,
        infoUrl: infoUrl,
        crossUrl: crossUrl,
        productImageData: productImageData,
        isOutSourced: isOutSourced,
        mixedCart: mixedCart,
        pickShowVal: pickShowVal,
        shipShowVal: shipShowVal,
        customBillingFieldsToggleOn: customBillingFieldsToggleOn,
        locationIcon: locationIcon,
        hcoinfoIcon: hcoinfoIcon,
        mapMarkerIcon: mapMarkerIcon,
        alertIcon: alertIcon,
        calenderIcon: calenderIcon,
        limitedTimeIcon: limitedTimeIcon,
        customBillingShipping: customBillingShipping,
        isSde: isSde,
        isSdeStore: isSdeStore,
        profileSession: profileSession,
        discountBreakdown: discountBreakdown,
        restrictedProductionLocation: restrictedProductionLocation,
        fclCustomerDefaultShippingData: fclCustomerDefaultShippingData,
        isEpro: isEpro,
        isShippingAccEditable: isShippingAccEditable,
        isProductionLocation: isProductionLocation,
        isCommercial: isCommercial,
        isCovidPeakSeason: isCovidPeakSeason,
        pickupSearchErrorDescription: pickupSearchErrorDescription,
        eproPickupAutofill: eproPickupAutofill,
        fclFirstName: fclFirstName,
        fclLastName: fclLastName,
        fclContactNumber: fclContactNumber,
        fclExtNumber: fclExtNumber,
        fclEmailAddress: fclEmailAddress,
        warningsHandling: warningsHandling,
        hcoPriceUpdate: hcoPriceUpdate,
        fclShippingDataStatus: fclShippingDataStatus,
        nameErrorMessage: nameErrorMessage,
        sdeSignatureMessage: sdeSignatureMessage,
        shippingAccountMsg: shippingAccountMsg,
        toastTitle: toastTitle,
        toastPickupContent: toastPickupContent,
        toastShippingContent: toastShippingContent,
        checkoutDeliveryMethodsTooltip: checkoutDeliveryMethodsTooltip,
        fixShippingResults: fixShippingResults,
        explorersD169768Fix: explorersD169768Fix,
        explorersRestrictedAndRecommendedProduction: explorersRestrictedAndRecommendedProduction,
        isCheckoutConfigAvailable: isCheckoutConfigAvailable,
        isLoggedIn: isLoggedIn,
        isFclCustomer: isFclCustomer,
        isSelfRegCustomer: isSelfRegCustomer,
        saveOrderBeforePayment: saveOrderBeforePayment,
        callRateApiShippingAccountValidation: callRateApiShippingAccountValidation,
        isShippingFormLoadBlankFixToggle: isShippingFormLoadBlankFixToggle,
        isExplorersApiErrorHandling: isExplorersApiErrorHandling,
        xmenEditPickupReview: xmenEditPickupReview,
        isUploadToQuote: isUploadToQuote,
        isExplorersD146371HideShippingFormFieldsFix: isExplorersD146371HideShippingFormFieldsFix,
        isExplorersAllowCityFieldCharactersFix: isExplorersAllowCityFieldCharactersFix,
        mediaUrl: mediaUrl,
        isCheckoutQuotePriceDashable: isCheckoutQuotePriceDashable,
        xmenOrderApprovalB2b: xmenOrderApprovalB2b,
        explorersD174773Fix: explorersD174773Fix,
        isRecipientAddressEnable: isRecipientAddressEnable,
        isCustomerShippingAccount3PEnabled: isCustomerShippingAccount3PEnabled,
        tigerE424573OptimizingProductCards: tigerE424573OptimizingProductCards,
        isEproUploadtoQuoteToggle: isEproUploadtoQuoteToggle,
        isExpectedDeliveryDateEnabled: isExpectedDeliveryDateEnabled,
        isPriorityPrintLimitedTimeToggle: isPriorityPrintLimitedTimeToggle,
        isPromiseTimePickupOptionsToggle: isPromiseTimePickupOptionsToggle,
        tiger_D195836FixLoadTimeHeroBanner: tiger_D195836FixLoadTimeHeroBanner,
        isFuseBidding: isFuseBidding,
        tiger_e486666: tiger_e486666,
        isB2532564ToggleEnable: isB2532564ToggleEnable,
        tiger_team_D_225000: tiger_team_D_225000,
        tiger_team_D_227679: tiger_team_D_227679,
        isCustomerAcknowledgementThirdPartyEnabled: isCustomerAcknowledgementThirdPartyEnabled,
        tiger_e468338: tiger_e468338,
        mazegeeksE482379AllowCustomerToChooseProductionLocationUpdates: mazegeeksE482379AllowCustomerToChooseProductionLocationUpdates,
        sgc_D_236651: sgc_D_236651,
        /**
         * Dynamically check if a given checkoutConfig toggle is enabled
         * @param {string} key
         * @returns {boolean}
         */
        isToggleEnabled: function(key) {
            return Boolean(window.checkoutConfig?.[key] || window.checkout?.[key]);
        },
    };
});
