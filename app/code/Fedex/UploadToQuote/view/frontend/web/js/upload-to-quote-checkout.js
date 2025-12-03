/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
	'jquery',
	'mage/url',
	'fedex/storage',
	'Magento_Ui/js/lib/view/utils/dom-observer',
	'Magento_Customer/js/customer-data',
    'Fedex_Delivery/js/model/pickup-data',
    'Fedex_Delivery/js/model/toggles-and-settings'
], function ($, urlBuilder, fxoStorage, $do, customerData, pickupData, togglesAndSettings) {
	'use strict';
	$(document).on("click", '.sidebar-print-instruction-container-checkout .sidebar-print-instruction-inner-container .close-icon', function () {
		$(".sidebar-print-instruction-container-checkout").remove();
	});

	var isSiteLevelQuoteStores = false;
	var isSiteLevelQuoteStoresToggle = false;
	var isSiteLevelQuoteLocation = false;
	let recommendedStores = window.checkoutConfig.recommended_production_location;
	let restrictedStores = window.checkoutConfig.restricted_production_location;
	var recommendedLocations = JSON.parse(recommendedStores);
	var restrictedLocations = JSON.parse(restrictedStores);
	var placeStateLocation = null;
	isSiteLevelQuoteStores = typeof (window.checkoutConfig.is_site_level_quoting_stores) != 'undefined' && window.checkoutConfig.is_site_level_quoting_stores != null ? window.checkoutConfig.is_site_level_quoting_stores : false;
	isSiteLevelQuoteLocation = typeof (window.checkoutConfig.is_site_level_quoting_location) != 'undefined' && window.checkoutConfig.is_site_level_quoting_location != null ? window.checkoutConfig.is_site_level_quoting_location : false;
	isSiteLevelQuoteStoresToggle = typeof (window.checkoutConfig.explorers_site_level_quoting_stores) != 'undefined' && window.checkoutConfig.explorers_site_level_quoting_stores != null ? window.checkoutConfig.explorers_site_level_quoting_stores : false;

	$(document).ready(function () {
		if (isSiteLevelQuoteStoresToggle && (isSiteLevelQuoteStores || isSiteLevelQuoteLocation)) {
			$do.get('#quote-pickup-search-form-container', function () {
				$("#quote-pickup-search-form-container").hide();
				$(".store-form-container").show();
			});
		}
	});

	$(document).ready(function () {
		//check if recommended or restricted is selected
		if ((isSiteLevelQuoteStores || isSiteLevelQuoteLocation) && isSiteLevelQuoteStoresToggle) {
			$do.get('.store-form-container', function () {
				if ((isSiteLevelQuoteStores && (!recommendedLocations || recommendedLocations.length === 0)) ||
                (isSiteLevelQuoteLocation && (!restrictedLocations || restrictedLocations.length === 0)))  {
					$('.stores').show();
					$('.multi-stores').hide();
					$('.location-name').text('No Store was selected in Admin Settings, please update stores list or stores preference');
					setTimeout(function() {
						$('#submit-quote-request').prop('disabled', true);
					}, 500);
				} else if (recommendedLocations.length === 1 || restrictedLocations.length === 1) {
					$('.stores').show();
					$('.multi-stores').hide();
					setTimeout(function() {
						$(".place-pickup-order").removeClass("place-pickup-order-disabled");
						$("#submit-quote-request").attr("disabled", false);
					}, 500);
					var location = recommendedLocations[0];
					var restrictedLocation = restrictedLocations[0];
					if(location){
						var addressLine = `${location.address1}, ${location.city}, ${location.state} ${location.postcode}`;
					} else if(restrictedLocation){
						var addressLine = `${restrictedLocation.address1}, ${restrictedLocation.city}, ${restrictedLocation.state} ${restrictedLocation.postcode}`;
					}
					$('.location-name').text(addressLine);
			} else if (recommendedLocations.length > 1 || restrictedLocations.length > 1) {
					$('.multi-stores').show();
					$('.stores .store-name').hide();
					setTimeout(function() {
						$(".place-pickup-order").removeClass("place-pickup-order-disabled");
						$("#submit-quote-request").attr("disabled", false);
					}, 500);
					let radioHTML = "";
					let isFirstLocation = true; // Flag to track the first location

					$.each(recommendedLocations || restrictedLocations, function (index, location) {
						var addressLine = `${location.address1}, ${location.city}, ${location.state} ${location.postcode}`;
						var hoursOfOperation = `${location.hours_of_operation}`;
						var operations = JSON.parse(hoursOfOperation);

						// Sort the operations array to ensure Monday to Sunday order
						var dayOrder = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
						operations.sort((a, b) => dayOrder.indexOf(a.day) - dayOrder.indexOf(b.day));

						var hoursHTML = '<div><span class="fa fa-clock-o icon-grey"></span><span>Hours Of Operation</span></div>';

						operations.forEach(operation => {
							hoursHTML += '<div class="shedule-container">';
							hoursHTML += '<div class="day">' + operation.day.substr(0, 3) + '</div>';
							hoursHTML += '<div class="range-main"><div class="range">';

							if (operation.schedule && operation.schedule !== 'Open') {
								hoursHTML += operation.schedule;
							} else if (operation.openTime === '00:00:00' && operation.closeTime === '24:00:00') {
								hoursHTML += 'Open 24hrs';
							} else if (operation.openTime && operation.openTime !== '00:00:00' && operation.closeTime && operation.closeTime !== '00:00:00') {
								var openTime = new Date('1970-01-01T' + operation.openTime);
								var closeTime = new Date('1970-01-01T' + operation.closeTime);

								function formatTime(date) {
									var hours = date.getHours();
									var minutes = date.getMinutes();
									var ampm = hours >= 12 ? 'PM' : 'AM';
									hours = hours % 12;
									hours = hours ? hours : 12;
									minutes = minutes < 10 ? '0' + minutes : minutes;
									return hours + ':' + minutes + ' ' + ampm;
								}

								hoursHTML += formatTime(openTime) + ' - ' + formatTime(closeTime);
							} else {
								hoursHTML += 'Closed';
							}

							hoursHTML += '</div></div></div>';
						});

						// Add 'checked' attribute to the first radio button
						var checkedAttribute = isFirstLocation ? 'checked' : '';
						var locationIcon = window.checkoutConfig.location_icon_image;
						radioHTML += `
						<div class="pickup-location-container" data-location-id="${location.location_id}">
							<label class="store-radio-btn">
								<input type="radio" name="store-list" value="${location.location_id}" ${checkedAttribute}>
								<strong>FedEx Office Print & Ship Center</strong><br>
								<span class="address-line"><img src="${locationIcon}" alt="Location Icon" class="location-icon"/>${addressLine}
								</span>
								<a href="#" class="show-details">SHOW DETAILS<span class="fa fa-angle-down"></span></a>
							</label>
							<div class="location-details" style="display: none;">
								<span class="fa fa-phone icon-grey"></span><span> ${location.telephone}</span><br>
								<span class="fa fa-envelope-square icon-grey"></span><span>${location.location_email}</span><br>
								<div class="hours-of-operation">
									${hoursHTML}
								</div>
							</div>
						</div>
						`;
						isFirstLocation = false;
					});
					$(".store-list").html(radioHTML);
				}
		    });
		}
	});

	$(document).ready(function () {
		$(document).on('click', '.show-details', function (e) {
			if (isSiteLevelQuoteStoresToggle) {
				e.preventDefault();
				var container = $(this).closest('.pickup-location-container');
				var detailsContainer = container.find('.location-details');
				if (detailsContainer.is(':hidden')) {
					detailsContainer.slideDown();
					$(this).html('HIDE DETAILS<span class="fa fa-angle-up"></span>');
				} else {
					detailsContainer.slideUp();
					$(this).html('SHOW DETAILS<span class="fa fa-angle-down"></span>');
				}
			}
		});
	});

	$(document).ready(function () {
		if (isSiteLevelQuoteStoresToggle) {
			$do.get('.store-list', function () {
				// Function to apply or remove border color based on selection
				function updateBorderColor($container, isSelected) {
					if (isSelected) {
						$container.css('border-color', '#4d148c'); // Apply purple border
					} else {
						$container.css('border', '1px solid #d3d3d3');
					}
				}
				// On page load, select the first radio button and apply border color
				var $firstRadio = $('input[name="store-list"]:first');
				$firstRadio.prop('checked', true);
				updateBorderColor($firstRadio.closest('.pickup-location-container'), true);

				$(document).on('change', 'input[name="store-list"]', function () {
					$('input[name="store-list"]').each(function () {
						updateBorderColor($(this).closest('.pickup-location-container'), this.checked);
					});
				});
				$(document).on('keydown', 'input[name="store-list"], .show-details', function (e) {
					var $current = $(this);
					var $container = $current.closest('.pickup-location-container');
					var $allContainers = $('.pickup-location-container');
					var currentIndex = $allContainers.index($container);

					switch (e.which) {
						case 9: // Tab key
							e.preventDefault(); // Prevent default tab behavior
							if (!e.shiftKey) {
								// Forward tab
								if ($current.is('input')) {
									// Move to SHOW DETAILS in the same container
									$container.find('.show-details').focus();
								} else {
									// If it's a SHOW DETAILS link, check for next container
									var $nextContainer = $allContainers.eq(currentIndex + 1);
									if ($nextContainer.length) {
										$nextContainer.find('input[name="store-list"]').focus();
									} else {
										// If no next container, focus on .contact-fname
										$('.contact-fname').focus();
									}
								}
							} else {
								// Backward tab (Shift+Tab)
								if ($current.is('.show-details')) {
									// Move to radio button in the same container
									$container.find('input[name="store-list"]').focus();
								} else {
									// Move to previous container's SHOW DETAILS
									var $prevContainer = $allContainers.eq(currentIndex - 1);
									if ($prevContainer.length) {
										$prevContainer.find('.show-details').focus();
									} else {
										// If no previous container, loop to last SHOW DETAILS
										$allContainers.last().find('.show-details').focus();
									}
								}
							}
							break;
						case 13: // Enter key
							if ($current.is('.show-details')) {
								e.preventDefault();
								$current.click();
							}
							break;
					}
				});
				// Handle focus
				$(document).on('focus', 'input[name="store-list"]', function () {
					$(this).prop('checked', true).change();
				});
				// When any radio button is clicked
				$('input[name="store-list"]').on('click', function () {
					// Remove purple border from previously selected container
					$('input[name="store-list"]').not(this).each(function () {
						updateBorderColor($(this).closest('.pickup-location-container'), false);
					});

					// Apply purple border to newly selected container
					updateBorderColor($(this).closest('.pickup-location-container'), true);
				});
			});
		}
	});

	$(document).on('keypress', '.sidebar-print-instruction-inner-container .close-icon', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            $(this).click();
        }
    });

	$(document).on('keypress', '.upload-to-quote #zipcodeLocation', function (e) {
		let keycode = (e.keyCode ? e.keyCode : e.which);
		if (keycode == '13') {
			$(".upload-to-quote #save-location-code-pickup").click();
		}
	});

	$(document).on('keypress', '.upload-to-quote #submit-quote-request', function (e) {
		let keycode = (e.keyCode ? e.keyCode : e.which);
		if (keycode == '13') {
			$(".upload-to-quote #submit-quote-request").click();
		}
	});

	//  D-161442
	$(document).on('keyup', '.contact-fname', function() {
		$(this).next('.contact-fname-error').hide();
		$(this).removeClass('contact-error');
	});

	$(document).on('keyup', '.contact-lname', function() {
		$(this).next('.contact-lname-error').hide();
		$(this).removeClass('contact-error');
	});

	$(document).on('keyup', '.contact-number', function() {
		$(this).next('.contact-number-error').hide();
		$(this).removeClass('contact-error');
	});

	$(document).on('keyup', '.contact-email', function() {
		$(this).next('.contact-email-error').hide();
		$(this).removeClass('contact-error');
	});

	/**
	 * Set cart items in local storage on click on review order
	 */
	$(document).on('click', '.fedex-account-number-review-button, .credit-card-review-button', function() {
		let isXmenOrderConfirmationFix = typeof (window.checkoutConfig.xmen_order_confirmation_fix) !== "undefined" && window.checkoutConfig.xmen_order_confirmation_fix !== null ? window.checkoutConfig.xmen_order_confirmation_fix : false;
		if (isXmenOrderConfirmationFix) {
			let cacheStorage = JSON.parse(window.localStorage.getItem('mage-cache-storage'));
			if (typeof (cacheStorage.cart.items) != "undefined" && cacheStorage.cart.items != null && cacheStorage.cart.items) {
				if(window.e383157Toggle) {
					fxoStorage.set("cart-items", cacheStorage.cart.items);
				} else {
					localStorage.setItem("cart-items", JSON.stringify(cacheStorage.cart.items));
				}
			}
	    }
	});

	return {

		/**
		 * Remove upload to quote local storage
		 *
		 * @return {object}
		 */
		removeUploadToQuoteLocalStorage: function () {
            if (window.e383157Toggle) {
                fxoStorage.delete("uploadtoquote_location_code_validation");
                fxoStorage.delete("uploadtoquote_state");
                fxoStorage.delete("uploadtoquote_city");
            } else {
                localStorage.removeItem("uploadtoquote_location_code_validation");
                localStorage.removeItem("uploadtoquote_state");
                localStorage.removeItem("uploadtoquote_city");
            }
		},

		/**
		 * Autofill contact form detail for Upload To Quote
		 *
		 */
		autoFillContactFormUploadToQuote: function (isFclCustomer) {
			$(document).ready(function () {
				function setInputValues() {
					let checkoutConfig = window.checkoutConfig;
					if ((isFclCustomer || checkoutConfig !== undefined) && checkoutConfig.fcl_login_customer_detail.first_name !== undefined) {
						let fclDetails = checkoutConfig.fcl_login_customer_detail;
						let fclFirstName = fclDetails.first_name;
						let fclLastName = fclDetails.last_name;
						let fclContactNumber = fclDetails.contact_number;
						let fclExtNumber = fclDetails.contact_ext;
						let fclEmailAddress = fclDetails.email_address;
						let postalcode = checkoutConfig.fcl_customer_default_shipping_data.postcode;
						let fnameElement = $(".upload-to-quote-contact-section .contact-name-container .contact-first-name .contact-fname");
						let lnameElement = $(".upload-to-quote-contact-section .contact-name-container .contact-last-name .contact-lname");
						let numberElement = $(".upload-to-quote-contact-section .contact-number-container .contact-phone-no .contact-number");
						let extElement = $(".upload-to-quote-contact-section .contact-number-container .contact-phone-ext .contact-ext");
						let emailElement = $(".upload-to-quote-contact-section .contact-email-container .contact-email");
						let postalcodeElement = $(".upload-to-quote .pickup-search-form-container .zipcode-container .zipcodeLocation");
						if (fnameElement.length > 0 && !fnameElement.val()) {
							fnameElement.val(fclFirstName);
						}
						if (lnameElement.length > 0 && !lnameElement.val()) {
							lnameElement.val(fclLastName);
						}
						if (numberElement.length > 0 && !numberElement.val()) {
							numberElement.val(fclContactNumber);
							numberElement.trigger("input").trigger("change");
						}
						if (extElement.length > 0 && !extElement.val()) {
							extElement.val(fclExtNumber);
						}
						if (emailElement.length > 0 && !emailElement.val()) {
							emailElement.val(fclEmailAddress);
						}
						if (postalcodeElement.length > 0 && !postalcodeElement.val()) {
							postalcodeElement.val(postalcode);
							postalcodeElement.trigger("change");
						}
						return (lnameElement.length > 0 && numberElement.length > 0 && extElement.length > 0 && emailElement.length > 0  && postalcodeElement.length > 0);
					}
					return false;
				}
				if (!setInputValues()) {
                    if(togglesAndSettings.isToggleEnabled('tiger_d238132')) {
                        window.addEventListener('uploadToQuoteFormLoaded', function () {
                            setInputValues();
                            // Make sure to re-validate the contact form after setting the values
                            $('.upload-to-quote-contact-section').trigger('change')
                        });
                    } else {
                        let observer = new MutationObserver(function (mutations) {
                            if (setInputValues()) {
                                observer.disconnect();
                            }
                        });
                        observer.observe(document.body, {
                            childList: true,
                            subtree: true
                        });
                    }
				}
			});
		},

		/**
		 * Location search autocomplete
		 *
		 * @return {object}
		 */
		locationSearchAutocomplete: function () {
			let self = this;

			this.removeUploadToQuoteLocalStorage();

			let input = document.querySelector('.zipcode-container').getElementsByTagName('input')[0];
			let autocomplete = new google.maps.places.Autocomplete(input);
			autocomplete.setComponentRestrictions({
				country: ["us"]
			});

			google.maps.event.addListener(autocomplete, 'place_changed', function () {
				let place = autocomplete.getPlace();
				if (typeof (place) != 'undefined' && typeof (place) == 'object' && Object.keys(place).length > 1) {
					let latitude = place.geometry.location.lat();
					let longitude = place.geometry.location.lng();

					if (latitude != "" && longitude != "") {
						$("#save-location-code-pickup").attr("disabled", false);
					}

					let address = place.address_components;

					address.forEach(function (component) {
						self.setLocationAutocompleteAddress(component);
					});

					$(".upload-to-quote-contact-section").trigger("change");
				}
			});
		},

		/**
		 * Set location geo finder address
		 *
		 * @return void
		 */
		setLocationAutocompleteAddress: function (component) {
			let types = component.types;

			if (types.indexOf('postal_code') > -1) {
				let pinCode = component.long_name;
				$("#zipcodeLocation").val(pinCode);
			}

			if (types.indexOf('administrative_area_level_1') > -1) {
                if(window.e383157Toggle){
                    fxoStorage.set("uploadtoquote_state", component.short_name);
                }else{
                    localStorage.setItem("uploadtoquote_state", component.short_name);
                }
			}

			if (types.indexOf('locality') > -1) {
                if(window.e383157Toggle){
                    fxoStorage.set('uploadtoquote_city', component.long_name);
                }else{
                    localStorage.setItem('uploadtoquote_city', component.long_name);
                }
			}
		},

		/**
		 * Get geo finder state from location address
		 *
		 * @return {object}
		 */
		locationGeoFinderAddressState: async function () {
			let stateCode = null;
			let countryCode = null;
			let searchAddress = document.querySelector('.zipcode-container').getElementsByTagName('input')[0].value;
			let geocoder = new google.maps.Geocoder();
			let message = null;
			let self = this;
			$('body').trigger('processStart');
			await geocoder.geocode({ 'address': searchAddress }, function (results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					let googleAddress = results[0].address_components;
					stateCode = null;
					googleAddress.forEach(function (component) {
						let types = component.types;
						if (types.indexOf('administrative_area_level_1') > -1) {
							stateCode = component.short_name;
                            if(window.e383157Toggle){
                                fxoStorage.set("uploadtoquote_state", component.short_name);
                            }else{
                                localStorage.setItem("uploadtoquote_state", component.short_name);
                            }
						}
						if (types.indexOf('locality') > -1) {
                            if(window.e383157Toggle){
                                fxoStorage.set('uploadtoquote_city', component.long_name);
                            }else{
                                localStorage.setItem('uploadtoquote_city', component.long_name);
                            }
						}
						if (types.indexOf('country') > -1) {
							countryCode = component.short_name;
                            if(window.e383157Toggle){
                                fxoStorage.set('uploadtoquote_country', countryCode);
                            }else{
                                localStorage.setItem('uploadtoquote_country',countryCode);
                            }
						}
					});
					self.handleValidationMessage(message);
					$('body').trigger('processStop');
				} else {
					message = 'The city or zip code you entered did not produce results. Please revisit your search criteria.<br>Transaction ID:f3d4c6f0-4908-4aeb-8c1b-ef0536afed95';
					self.handleValidationMessage(message);
					$('body').trigger('processStop');
				}
			});
			return {
				stateCode: stateCode,
				countryCode: countryCode
			};
		},

		/**
		 * Handle validation message
		 *
		 * @return void
		 */
		handleValidationMessage: function (message) {
			if (message) {
				$('.upload-to-quote .error-container').removeClass('upload-to-quote-api-error-hide');
				$('.upload-to-quote .error-container').removeClass('api-error-hide');
				$('.upload-to-quote .message-container').html(message);
				$('.upload-to-quote .message-container').addClass('upload-to-quote-zipcode-error');
			} else {
				$('.upload-to-quote .error-container').addClass('upload-to-quote-api-error-hide');
				$('.upload-to-quote .message-container').html('');
				$('.upload-to-quote .message-container').removeClass('upload-to-quote-zipcode-error');
			}
		},

		/**
		 * Enable save button by zipcode
		 *
		 * @return void
		 */
		enableSaveButtonByZipcode: function (zipcode) {
			if (zipcode.length > 4) {
				var inBranchdata = customerData.get('inBranchdata')();
				if (isSiteLevelQuoteStoresToggle && inBranchdata.isInBranchDataInCart && window.checkoutConfig.is_quote_price_is_dashable) {
					$("#zipcodeLocation").attr('disabled', 'disabled').css({
						'background-color': '#c1baba'
					});
					$("#save-location-code-pickup").attr("disabled", 'disabled');
				} else {
					$("#save-location-code-pickup").removeAttr("disabled");
				}
			} else {
				$("#save-location-code-pickup").attr("disabled", true);
			}
		},

		/**
		 * Save location code
		 *
		 * @return void
		 */
		saveLocationCode: async function () {
			let requestUrl = urlBuilder.build("uploadtoquote/index/savelocationcode");
			let stateCode = null;
			let countryCode = null;
			let message = null;
			let self = this;
			let locationid = null;
			let uploadToQuoteLocationCodeValidation = null;
			let recommendedStores = window.checkoutConfig.recommended_production_location;
			let recommendedLocations = JSON.parse(recommendedStores);
			let restrictedStores = window.checkoutConfig.restricted_production_location;
			var restrictedLocations = JSON.parse(restrictedStores);
			let locationData = null;
			// Get the selected radio option
			if ((isSiteLevelQuoteStoresToggle && isSiteLevelQuoteStores || isSiteLevelQuoteLocation) || 
                (window.checkoutConfig?.tiger_team_E_469378_u2q_pickup && pickupData.selectedPickupLocation())
            ) {
				let selectedLocation = $('input[name="store-list"]:checked').val();
				if (recommendedLocations && recommendedLocations.length > 0) {
					if (recommendedLocations.length === 1) {
						locationData = selectedLocation = recommendedLocations[0];
					} else {
						locationData = recommendedLocations.find(location => location.location_id === selectedLocation);
					}
				}
				if (restrictedLocations && restrictedLocations.length > 0) {
					if(restrictedLocations.length === 1) {
						locationData = selectedLocation = restrictedLocations[0];
					} else {
						locationData = restrictedLocations.find(location => location.location_id === selectedLocation);
					}
				}

                if (window.checkoutConfig?.tiger_team_E_469378_u2q_pickup && pickupData.selectedPickupLocation()) {
                    locationData = {
                        state: pickupData.selectedPickupLocation().address?.stateOrProvinceCode,
                        country_id: pickupData.selectedPickupLocation().address?.countryCode,
                        location_id: pickupData.selectedPickupLocation().id
                    };
                }
				if (locationData) {
					stateCode = locationData.state;
					countryCode = locationData.country_id;
					locationid = locationData.location_id;
					this.placeStateLocation = locationData?.state;
					if (stateCode) {
						$.ajax({
							url: requestUrl,
							type: "POST",
							data: { id: locationid, stateCode: stateCode, countryCode: countryCode },
							dataType: "json",
							showLoader: true,
							async: false,
						}).done(function (response) {
							if (response.success) {
								if (window.e383157Toggle) {
									fxoStorage.set('uploadtoquote_location_code_validation', true);
									uploadToQuoteLocationCodeValidation = fxoStorage.get("uploadtoquote_location_code_validation");
								} else {
									localStorage.setItem('uploadtoquote_location_code_validation', true);
									uploadToQuoteLocationCodeValidation = localStorage.getItem("uploadtoquote_location_code_validation");
								}
							} else {
								message = 'System error, Please try again.';
								self.handleValidationMessage(message);
							}
						});
					} else {
						message = 'The city or zip code you entered did not produce results. Please revisit your search criteria.<br>Transaction ID:f3d4c6f0-4908-4aeb-8c1b-ef0536afed95';
						self.handleValidationMessage(message);
					}

				}
				else {
					message = 'Invalid state or country code for the selected location.';
					self.handleValidationMessage(message);
				}
			} else {
				await self.locationGeoFinderAddressState().then(function (res) {
					stateCode = res.stateCode;
					countryCode = res.countryCode;

					if (stateCode) {
						$.ajax({
							url: requestUrl,
							type: "POST",
							data: { stateCode: stateCode, countryCode: countryCode },
							dataType: "json",
							showLoader: true,
							async: false,
						}).done(function (response) {
							if (response.success) {
								if (window.e383157Toggle) {
									fxoStorage.set('uploadtoquote_location_code_validation', true);
									uploadToQuoteLocationCodeValidation = fxoStorage.get("uploadtoquote_location_code_validation");
								} else {
									localStorage.setItem('uploadtoquote_location_code_validation', true);
									uploadToQuoteLocationCodeValidation = localStorage.getItem("uploadtoquote_location_code_validation");
								}
							} else {
								message = 'System error, Please try again.';
								self.handleValidationMessage(message);
							}
						});
					} else {
						message = 'The city or zip code you entered did not produce results. Please revisit your search criteria.<br>Transaction ID:f3d4c6f0-4908-4aeb-8c1b-ef0536afed95';
						self.handleValidationMessage(message);
					}
				});
			}

			return {
				uploadToQuoteLocationCodeValidation: uploadToQuoteLocationCodeValidation
			};
		},

		/**
		 * Get Upload To Quote Information
		 *
		 * @return json
		 */
		getUploadToQuoteInfo: function () {
			let contact_number = $('.contact-number').val().replace(" ", "").replace("(", "").replace(")", "").replace("-", '');
            		let uploadToQuoteCity = window.e383157Toggle
                		? fxoStorage.get('uploadtoquote_city')
                		: localStorage.getItem('uploadtoquote_city');
            		let uploadToQuoteState = window.e383157Toggle
                		? fxoStorage.get('uploadtoquote_state')
                		: localStorage.getItem('uploadtoquote_state');
			return {
				contactInformation: {
					contact_fname: $('.contact-fname').val(),
					contact_lname: $('.contact-lname').val(),
					contact_email: $('.contact-email').val(),
					contact_number: contact_number,
					contact_number_pickup: '',
					contact_ext: $('.contact-ext').val(),
					alternate_fname: '',
					alternate_lname: '',
					alternate_email: '',
					alternate_number: '',
					alternate_ext: '',
					isAlternatePerson: '',
				},
				addressInformation: {
					pickup_location_name: '',
					pickup_location_street: '',
					pickup_location_city: uploadToQuoteCity ?? '',
					pickup_location_state: uploadToQuoteState ?? this.placeStateLocation,
					pickup_location_zipcode: pickupData.selectedPickupLocation()?.address?.postalCode ?? $('#zipcodeLocation').val(),
					pickup_location_country: 'US',
					pickup_location_date: '',
					pickup: true,
					shipping_address: '',
					billing_address: '',
					shipping_method_code: '',
					shipping_carrier_code: '',
					shipping_detail: {
						carrier_code: '',
						method_code: '',
						carrier_title: '',
						method_title: '',
						amount: 0,
						base_amount: 0,
						available: false,
						error_message: "",
						price_excl_tax: 0,
						price_incl_tax: 0,
					},
				},
				rateapi_response: ""
			}
		},

		/**
		 * Submit Upload To Quote
		 *
		 * @return void
		 */
		submitUploadToQuote: function () {
			let self = this;
			let submitUploadToQuoteRequest = JSON.stringify(this.getUploadToQuoteInfo()).replaceAll('&', encodeURIComponent('&'));

			$.ajax({
				url: urlBuilder.build(
					"delivery/quote/createpost"
				),
				type: "POST",
				data: "data=" + submitUploadToQuoteRequest,
				dataType: "json",
				showLoader: true,
				async: true,
				complete: function () { },
			}).done(function (resData) {
				if (resData.url && resData.quoteId) {
                    window.location.href = resData.url;
                    if (window.e383157Toggle) {
                        fxoStorage.set("uploadtoquote_quoteid", resData.quoteId);
                    } else {
                        localStorage.setItem("uploadtoquote_quoteid", resData.quoteId);
                    }
                    self.handleValidationMessage('');
					self.removeUploadToQuoteLocalStorage();
				} else {
					let message = 'System error, Please try again.';
					self.handleValidationMessage(message);
				}
			});
		},
	};
});
