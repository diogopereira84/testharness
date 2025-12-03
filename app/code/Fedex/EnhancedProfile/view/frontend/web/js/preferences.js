/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(["jquery",
        "mage/url",
        "Magento_Ui/js/modal/modal",
        "fedex/storage"
    ],
    function(
        $,
        url,
        modal,
        fxoStorage
    ){
    "use strict";

    let onEnterSearch = true;

    // Marker properties
    var selectedMarkerColor = '#4d148c';
    var defaultMarkerColor = '#54646b';
    var markerGlyphColor = '#ffffff';
    var markerScale = 1.35;
    var selected_icon = null;
    var default_icon = null;

    if (typeof(window.validateFdxLogin) != 'undefined' && window.validateFdxLogin == '') {
        $(".err-msg").show();
        $(".err-msg .message").html('System error, Please try again.</span>');
    }

    $("#account-number").on('keypress',$.proxy(function (evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }, this));

    $("#account-number").on('paste', $.proxy(function (event) {
        if (event.originalEvent.clipboardData.getData('Text').match(/[^\d]/)) {
            event.preventDefault();
        }
    }, this));

    $(".peferred-delivery-method").on('change', function() {
        let _this = this;
        let preferredDeliveryMethod = $(_this).val();

        if (preferredDeliveryMethod == 'Pick up at store') {
            $("#default_shipping_address").addClass('disabled');
            $(".pick-up-store-cotainer").removeClass('disabled');
            pickupAtStorePreferredDeliveryMethod();
        } else if (preferredDeliveryMethod == 'Ship to address') {
            $(".pick-up-store-cotainer").addClass('disabled');
            $("#default_shipping_address").removeClass('disabled');
            shipToAddressPreferredDeliveryMethod();
        } else {
            $(".pick-up-store-cotainer").hide();
            $(".pick-up-store-cotainer").removeClass('disabled');
            $("#default_shipping_address").empty();
            $("#default_shipping_address").removeClass('disabled');
        }
        $(".succ-msg").hide();
        $(".err-msg").hide();
    });

    /**
     * Set Preferred Delivery Method As a Shipping
     */
    function shipToAddressPreferredDeliveryMethod() {
        let  customShppingInfo =  url.build('fcl/customer/customershppinginfo');
        $.ajax({
            type: "POST",
            url: customShppingInfo,
            data: [],
            cache: false,
            showLoader: true
        }).done(function (data) {
            let dataBtn = "";
            if(window.isE464167SSOCustomerEnabled){
                if(window.isSSOLogin){
                    data = "";
                    if(window.isSSOGroup){
                        if (window.preferredDeliveryMethod == 'DELIVERY') {
                    dataBtn = "<div class='save-preffered-ship-address' disabled='disabled'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY' disabled='disabled' /></div>";
                    } else {
                        dataBtn = "<div class='save-preffered-ship-address'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY'/></div>";
                    }
                    
                    data = data + "<a href='javascript:void(0)' class='edit-account-link' data-url='window.profileurl' id='preffered-ship-address-update'><span>Edit</span></a>" +dataBtn;
                    $("#default_shipping_address").html('<div class="default-shipping-address-title">Default Shipping Address</div>'+data);
                    }
                } else {
                    if (window.preferredDeliveryMethod == 'DELIVERY') {
                    dataBtn = "<div class='save-preffered-ship-address' disabled='disabled'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY' disabled='disabled' /></div>";
                    } else {
                        dataBtn = "<div class='save-preffered-ship-address'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY'/></div>";
                    }
                    
                    data = data + "<a href='javascript:void(0)' class='edit-account-link' data-url='window.profileurl' id='preffered-ship-address-update'><span>Edit</span></a>" +dataBtn;
                    $("#default_shipping_address").html('<div class="default-shipping-address-title">Default Shipping Address</div>'+data);
                }

            }else{
                if (window.preferredDeliveryMethod == 'DELIVERY') {
                    dataBtn = "<div class='save-preffered-ship-address' disabled='disabled'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY' disabled='disabled' /></div>";
                } else {
                    dataBtn = "<div class='save-preffered-ship-address'><input type='button' id='delivery_save_changes' class='preferred-delivery' value='SAVE CHANGES' data-method='DELIVERY'/></div>";
                }
                
                data = data + "<a href='javascript:void(0)' class='edit-account-link' data-url='window.profileurl' id='preffered-ship-address-update'><span>Edit</span></a>" +dataBtn;
                $("#default_shipping_address").html('<div class="default-shipping-address-title">Default Shipping Address</div>'+data);
            }
        });
    }

    /**
     * Set Preferred Delivery Method as a Pickup
     */
    function pickupAtStorePreferredDeliveryMethod() {
        $(".pick-up-store-cotainer").show();
        $("#btn-remove-zip").trigger('click');

        if(window.preferredStore.toUpperCase() == 'NULL'){
            $(".map-chooser").show();
            $(".preferred-store").show();
            $("#preferred-location").hide();
            $(".preferred-delivery-pickup").hide();
        } else if (window.preferredStore != ''){
            $(".map-chooser").hide();
            $(".preferred-store").hide();
            $("#preferred-location").show();
            $(".preferred-delivery-pickup").show();
        }
    }

    $(".pickup-search-radius").on('change', function() {
        let _this = this;
        let radius = $(_this).val();
        if (radius == 100) {
            $('.location-message').hide();
        } else {
            $('.location-message').show();
            $('.no-location-message').hide();
        }
    });

    let city = null;
    let stateCode = null;
    let pinCode = null;
    let radius = null;
    let flagData = false;
    let userInteracted = false;
    if (window.checkout?.tech_titans_d_217639) {
        $("#zipcoderistricted.radiusBox").on("click focus", function () {
            userInteracted = true;
        });
    }
    $("#zipcoderistricted.radiusBox").on("keypress keyup", function (e) {
        let self = this;
        radius = $("#pickup-search-radius").val();
        let searchZipCodeInput = document.querySelector('.zipcode-container').getElementsByTagName('input')[0];

        let shouldShowAutocomplete = window.checkout?.tech_titans_d_217639
            ? (searchZipCodeInput.value.length >= 2 && userInteracted)
            : (searchZipCodeInput.value.length >= 2);

        if (shouldShowAutocomplete) {
            let autocomplete = new google.maps.places.AutocompleteService();
            let htmlDiv = resetResults();
            autocomplete.getPredictions({input: searchZipCodeInput.value, componentRestrictions: {country: 'us'}},
                predictions => {
                    predictions.forEach(function (component) {
                        var div = document.createElement('div');
                        $(div).addClass('result-wrapper');

                        let hightlightedResult = function (input, result) {
                            let hightlightedResult = result;

                            // Making all words from input to be capitalized for a better match
                            input = input.replace(/\b\w/g, function (match) {
                                return match.toUpperCase();
                            });

                            // searching and making it bold if found
                            if (result.indexOf(input) !== -1) {
                                hightlightedResult = result.replace(input, "<b>" + input + "</b>");
                            }

                            return hightlightedResult;
                        };

                        let input = searchZipCodeInput.value;
                        let result = component.description;

                        div.innerHTML += "<div class='result'>" + hightlightedResult(input, result) + "</div>";
                        // Bind a click event
                        div.onclick = function () {
                            let geocoder = new google.maps.Geocoder();
                            geocoder.geocode({'address': component.description}, function (results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    let latitude = results[0].geometry.location.lat();
                                    let longitude = results[0].geometry.location.lng();

                                    if (latitude != "" && longitude != "") {
                                        $("#search-pickup").attr("disabled", false);
                                    }

                                    let address = results[0].address_components;
                                    setAddressInMap(address, latitude, longitude, radius);
                                    resetResults();
                                }
                            });
                        }
                        htmlDiv.appendChild(div);
                    });
                });

            // Enter event in search field
            if (e.which == 13) {
                if (window.e383157Toggle) {
                    fxoStorage.set("setZipCode", true);
                } else {
                    localStorage.setItem("setZipCode", true);
                }
                onEnterSearch = true;
            }
        } else {
            resetResults();
        }

        let setZipCode = false;
        if(window.e383157Toggle){
            setZipCode = fxoStorage.get("setZipCode") || false;
        }else{
            setZipCode = localStorage.getItem("setZipCode") || false;
        }
        if (setZipCode) {
            pinCode = $(this).val();
            if(window.e383157Toggle){
                fxoStorage.delete("setZipCode");
            }else{
                localStorage.removeItem("setZipCode");
            }
        }
        if (e.which == 13 && $(this).val() != '') {
            // On enter Enter key it is calling for search store pickup address
            if (onEnterSearch) {
                let searchAddressData = geoCoderFindAddress(city, stateCode, pinCode);
                city = searchAddressData.city;
                stateCode = searchAddressData.stateCode;
                pinCode = searchAddressData.pinCode;
                onEnterSearch = false;
            getPickupAddress(city, stateCode, pinCode, radius);
            } else {
                onEnterSearch = true;
            }
        }
    });

    function setAddressInMap(address, lat, lng, radius) {
        let city = null,
            stateCode = null,
            pinCode = null;

        address.forEach(function (selected) {
            let types = selected.types;
            if (types.indexOf('locality') > -1) {
                city = selected.long_name;
            }
            if (types.indexOf('administrative_area_level_1') > -1) {
                stateCode = selected.short_name;
            }
            if (types.indexOf('postal_code') > -1) {
                pinCode = selected.long_name;
                $(".zipcodePickup").val(pinCode);
            }
        });
        if (pinCode == null) {
            pinCode = $(".zipcodePickup").val();
        }
        getPickupAddress(city, stateCode, pinCode, radius, lat, lng);
    }

    // GeoCoder Find Address
    function geoCoderFindAddress(city, stateCode, pinCode) {
        let searchAddress = document.querySelector('.zipcode-container').getElementsByTagName('input')[0].value;
        let geocoder = new google.maps.Geocoder();
        geocoder.geocode({ 'address': searchAddress }, function (results, status) {
            if (status != google.maps.GeocoderStatus.OK) {
                city  = null;
                stateCode = null;
                pinCode = null;
            }
            if (status == google.maps.GeocoderStatus.OK) {
                let googleAddress = results[0].address_components;
                city  = null;
                stateCode = null;
                pinCode = null;
                googleAddress.forEach(function(component) {
                    let types = component.types;
                    if (types.indexOf('locality') > -1)  {
                        city = component.long_name;
                    }
                    if (types.indexOf('administrative_area_level_1') > -1) {
                        stateCode = component.short_name;
                    }
                    if (types.indexOf('postal_code') > -1) {
                        pinCode = component.long_name;
                    }
                });
            }
        });

        return {
            city: city,
            stateCode: stateCode,
            pinCode: pinCode
        };
    }

    function resetResults() {
        let htmlDiv = document.getElementById('geocoder-results');
        htmlDiv.innerHTML = '';
        return htmlDiv;
    }

    // Get Pickup Address
    function getPickupAddress(city, stateCode, pinCode, radius,  lat = null, lng = null) {

        let linkUrl = url.build('customer/account/getlocation');
        let html = "";
        let range = '';
        let latdis = '';
        let lngdis = '';
        latdis = lat;
        lngdis = lng;

        let geocoder = new google.maps.Geocoder();

        if (!latdis) {
            geocoder.geocode({ 'address': $("#zipcoderistricted").val() }, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    latdis = results[0].geometry.location.lat();
                    lngdis = results[0].geometry.location.lng();
                } else {
                    console.log("Couldn't get your location");
                }
            });
        }

     $.ajax({
         url: linkUrl,
         showLoader: true,
         type: "POST",
         dataType: 'json',
         data: {
             city: city,
             stateCode: stateCode,
             zipcode: pinCode,
             radius: radius,
         },
         success: function (result) {
             if(result.status == 'error') {
                 if (result.hasOwnProperty("noLocation") ) {
                     $('#errmsg').hide();
                     if ($(".pickup-search-radius").val() == 100) {
                         $('.no-location-message').show();
                     }
                  } else {
                     $('.no-location-message').hide();
                     $('#errmsg').show();
                     $('#errmsg').text(result.message);
                  }
                 $('#googleMap').hide();
                 $('#restricted_stores').hide();
                 $(".btn-set-preffered-store-container").hide();
                 return;
             }
             $('#errmsg').hide();
             $('.no-location-message').hide();
             if($(".toggle-input").is(":checked")) {
                 $(".map-canvas").show();
             } else {
                 $(".map-canvas").hide();
             }
             $('#restricted_stores').show();
             $(".btn-set-preffered-store-container").show();

            if (result.locations.length <= 3) {
                $(".pick-up-store-cotainer .restricted-stores").css("height", "auto");
            } else {
                $(".pick-up-store-cotainer .restricted-stores").css("height", "446px");
            }

             let  Maplocations = [];
             for (let  i = 0; i < result.locations.length; i++) {
                 // D-217639 Unable to update the preferred pickup location in FCL profile (Retail/Commercial)
                 let name, address1, locationid, phone, email, locationType;
                 if(window.checkout?.tech_titans_d_217639) {
                    name = result.locations[i].locationName;
                    address1 = result.locations[i].address?.streetLines?.[0] || '';
                    locationid = result.locations[i].officeLocationId;
                    phone = result.locations[i].phoneNumber;
                    email = result.locations[i].emailAddress;
                    locationType = result.locations[i].locationFormat;
                 } else {
                    name = result.locations[i].name;
                    address1 = result.locations[i].address.address1;
                    locationid = result.locations[i].Id;
                    phone = result.locations[i].phone;
                    email = result.locations[i].email;
                    locationType = result.locations[i].locationType;
                 }
                 let  city = result.locations[i].address.city;
                 let  stateOrProvinceCode = result.locations[i].address.stateOrProvinceCode;
                 let  postalCode = result.locations[i].address.postalCode;
                 let  latitude = result.locations[i].geoCode.latitude;
                 let  longitude = result.locations[i].geoCode.longitude;

                 let  latlong = { lat: latitude, lng: longitude, title: name };

                 Maplocations.push(latlong);

                 const formatter = new Intl.NumberFormat('en-US', {
                     minimumFractionDigits: 2,
                     maximumFractionDigits: 2,
                 });
                 let  R = 3958.8; // Radius of the Earth in miles
                 let  rlat1 = latdis * (Math.PI/180); // Convert degrees to radians
                 let  rlat2 = latitude * (Math.PI/180); // Convert degrees to radians
                 let  difflat = rlat2-rlat1; // Radian difference (latitudes)
                 let  difflon = (longitude-lngdis) * (Math.PI/180); // Radian difference (longitudes)

                 let  d = 2 * R * Math.asin(Math.sqrt(Math.sin(difflat/2)*Math.sin(difflat/2)+Math.cos(rlat1)*Math.cos(rlat2)*Math.sin(difflon/2)*Math.sin(difflon/2)));
                 let  distance =  formatter.format(d) + ' mi' ;
                 console.log(distance);
                 let isHideLocation = null;
                 isHideLocation = i > 9 ? 'hide-pickup-locations' : '';
                 html += '<div class= "list-container '+ isHideLocation +'">';
                 html += '<div class="box-container">';
                 html += '<label class="custom-radio-btn pick-up-button">';
                 html += '<span class="pickup-location-id" name="locationid" value="'+locationid +'" hidden>'+locationid + '</span>';
                 html += '<input type="radio" name="options[]" class="chk" value="'+locationid +'" data-lat="'+latitude+'" data-lng="'+longitude+'" data-location-type="'+locationType+'" />';
                 html += '<span class="radio-label_name" tabindex="0"><span>'+name + '</span></span>';
                 html += '<span class="distance-map">'+distance + '</span>';
                 html += '</label>';
                 html += '<div class="pickup-address">';
                 html += '<div>';
                 html += '<span class="fa fa-map-marker icon-grey"></span>';
                 html += '<span class="pickup-location-street">'+address1 + '</span>';
                 html += '<span>,</span>';
                 html += '<span class="pickup-location-city">'+city + '</span>';
                 html += '<span>,</span>';
                 html += '<span class="pickup-location-state">'+stateOrProvinceCode + '</span>';
                 html += '<span>,</span>';
                 html += '<span class="pickup-location-zipcode">'+postalCode + '</span>';
                 html += '</div>';
                 if (locationType == 'HOTEL_CONVENTION') {
                     html += '<div class="hotel-container">';
                     html += '<img src="'+window.mediaJsImgpath+ 'wysiwyg/images/hotel.png" class="hotel-icon" alt="Hotel & Convention Location"/>';
                     html += '<span class="pickup-location-hotel">Hotel & Convention Location</span>';
                     html += '</div>';
                 }
                 html += '</div>';

                 html += '<div class="show-details-container" style="display:block">';
                 html += '<div class="show-details-button" tabindex="0">';
                 html += 'SHOW DETAILS';
                 html += '<span class="fa fa-angle-down"></span>';
                 html += '</div>';
                 html += '</div>';

                 html += '<div class="center-details-main" style="display:none">';
                 html += '<div class="center-details">';
                 html += '<div>';
                 html += '<span class="fa fa-phone icon-grey"></span>';
                 html += '<span >'+phone + '</span>';
                 html += '</div>';
                 html += '<div>';
                 html += '<span class="fa fa-envelope-square icon-grey"></span>';
                 html += '<span >'+email + '</span>';
                 html += '</div>';
                 html += '<div>';
                 html += '<span class="fa fa-clock-o icon-grey"></span>';
                 html += '<span>Hours Of Operation</span>';
                 html += '</div>';

                 // D-217639 Unable to update the preferred pickup location in FCL profile (Retail/Commercial)
                 if(window.checkout?.tech_titans_d_217639) {
                    for (let  j = 1; j < result?.locations[i]?.operatingHours?.length; j++) {
                            if (!result?.locations[i]?.operatingHours?.length) {
                                continue
                            }
                            let  date = result.locations[i].operatingHours[j].date;
                            let  days = result.locations[i].operatingHours[j].dayOfWeek;
                            let  day = days.slice(0, 3);
                            let  schedule = result.locations[i].operatingHours[j].schedule;
                            let  openTime = result.locations[i].operatingHours[j].openTime;
                            let  closeTime = result.locations[i].operatingHours[j].closeTime;
                            if(openTime == undefined){
                                range = 'Closed';
                            }else{
                                range = openTime +'-'+ closeTime;
                            }

                            html += '<div class = "hours_of_opp">';
                            html += '<div class="shedule-container">';
                            html += '<div class="day" >'+day+'   </div>';
                            html += '<div class ="range-main">';
                            html += '<div class ="range">'+range+'</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                    }
                 } else {
                    for (let  j = 1; j < result.locations[i].hoursOfOperation.length; j++) {

                         let  date = result.locations[i].hoursOfOperation[j].date;
                         let  days = result.locations[i].hoursOfOperation[j].day;
                         let  day = days.slice(0, 3);
                         let  schedule = result.locations[i].hoursOfOperation[j].schedule;
                         let  openTime = result.locations[i].hoursOfOperation[j].openTime;
                         let  closeTime = result.locations[i].hoursOfOperation[j].closeTime;
                         if(openTime == undefined){
                             range = 'Closed';
                         }else{
                             range = openTime +'-'+ closeTime;
                         }

                         html += '<div class = "hours_of_opp">';
                         html += '<div class="shedule-container">';
                         html += '<div class="day" >'+day+'   </div>';
                         html += '<div class ="range-main">';
                         html += '<div class ="range">'+range+'</div>';
                         html += '</div>';
                         html += '</div>';
                         html += '</div>';
                    }
                 }
                 if(!window.checkout?.tech_titans_d_217639) {
                    let  date = result.locations[i].hoursOfOperation[0].date;
                    let  days = result.locations[i].hoursOfOperation[0].day;
                    let  day = days.slice(0, 3);
                    let  schedule = result.locations[i].hoursOfOperation[0].schedule;
                    let  openTime = result.locations[i].hoursOfOperation[0].openTime;
                    let  closeTime = result.locations[i].hoursOfOperation[0].closeTime;
                    if(openTime == undefined){
                        range = 'Closed';
                    }else{
                        range = openTime +'-'+ closeTime;
                    }

                    html += '<div class = "hours_of_opp">';
                    html += '<div class="shedule-container">';
                    html += '<div class="day" >'+day+'   </div>';
                    html += '<div class ="range-main">';
                    html += '<div class ="range">'+range+'</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                 }

                 html += '<div class="account-hide-details-button" style="display:block" tabindex="0">HIDE DETAILS';
                 html += '<span class="fa fa-angle-up"></span>';
                 html += '</div>';
                 html += '</div>';
                 html += '</div>';
                 html += '</div>';
                 html += '</div>';
             }

             const uluru = {lat: parseFloat(Maplocations[0].lat), lng: parseFloat(Maplocations[0].lng)};
             // The map, centered at Uluru
             const map = new google.maps.Map(document.getElementById("googleMap"), {
                 zoom: 12,
                 center: uluru,
                 mapId: "DEMO_MAP_ID"
             });
                $('.show-more-locations').remove();
                html += '<div class="show-more-locations">';
                html += ' <button title="Show More Locations">Show More Locations</button>';
                html += '</div>';

             let  markers = [];
             Maplocations.forEach(function (location, index) {

                 default_icon = new google.maps.marker.PinElement({
                     glyph: `${index + 1}`,
                     background: defaultMarkerColor,
                     borderColor: defaultMarkerColor,
                     glyphColor: markerGlyphColor,
                     scale: markerScale
                 });

                 selected_icon = new google.maps.marker.PinElement({
                     glyph: `${index + 1}`,
                     background: selectedMarkerColor,
                     borderColor: selectedMarkerColor,
                     glyphColor: markerGlyphColor,
                     scale: markerScale
                 });

                 let marker = new google.maps.marker.AdvancedMarkerElement({
                     map,
                     position: {'lat':parseFloat(location.lat),'lng':parseFloat(location.lng)},
                     content: index === 0 ? selected_icon.element : default_icon.element
                     });

                     markers.push(marker);
                     let  infowindow = new google.maps.InfoWindow;
                     infowindow.setContent(location.title);
                     marker.addListener("click", () => {
                     infowindow.open({
                     anchor: marker,
                     map,
                     shouldFocus: false,
                     });

                 });

             });

             $( "#restricted_stores" ).html(html);
            if (window.checkout?.tech_titans_d_217639) {
                $(".pickup-hco-info-msg").hide();
            }
            let elementShowMoreLocations = $('.show-more-locations');
            elementShowMoreLocations.off('click');
            elementShowMoreLocations.on('click', function () {
                $('.restricted-stores .hide-pickup-locations').each(function (index) {
                    if (index < 5) {
                        $(this).removeClass('hide-pickup-locations');
                    }
                });
            });
             setTimeout(function() {
                 if ($('#preferred-location .pickup-location-id')) {
                     let  preferredLocationId = $('#preferred-location .pickup-location-id').val();
                     let  preferredZipCode = $('#preferred-location .pickup-location-zipcode').html().trim();
                     let  zipcoderistricted = $('#zipcoderistricted').val();
                     if (preferredZipCode == zipcoderistricted) {
                         $("input[class=chk][value=" + preferredLocationId + "]").trigger('click');
                     }
                 }
             }, 1000);

             $(".show-details-button").on('click', function() {
                 let  _this = this;
                 $(_this).parent(".show-details-container").next(".center-details-main").show();
                 $(_this).parent(".show-details-container").hide();
             });

             $(".account-hide-details-button").on('click', function() {
                 let  _this = this;
                 $(_this).parent(".center-details").parent(".center-details-main").hide();
                 $(_this).parent(".center-details").parent(".center-details-main").prev(".show-details-container").show();
             });

             $(".chk").on('click', function() {
                 let  _this = this;
                 let  lat = $(_this).attr("data-lat");
                 let  lng = $(_this).attr("data-lng");
                 map.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
                 map.setZoom(15);
                 let  loctype = $(_this).attr("data-location-type");
                 if (loctype =="HOTEL_CONVENTION") {
                     $(".pickup-hco-info-msg").show();
                 }
                 else {
                     $(".pickup-hco-info-msg").hide();
                 }
                 $(".list-container").removeClass("selected")
                 $(_this).parent(".pick-up-button").parent(".box-container").parent(".list-container").addClass("selected")
             });
         }
     });
    }

    $(".toggle-input").on('click', function() {
        if($(this).is(":checked")) {
            $(".map-canvas").show();
        } else {
            $(".map-canvas").hide();
        }
    });

    $(".account .input-icons #btn-remove-zip").on('click', function() {
      let  _this = this;
      $('#zipcoderistricted').val('');
      $( "#restricted_stores" ).html('');
      $("#restricted_stores").hide();
      $(".pickup-hco-info-msg").hide();
      $(".map-canvas").hide();
      $(_this).hide();
      $(".btn-set-preffered-store-container").hide();
    });

    /* FedEx Account Form Validation */
    $(document).ready(function () {
        if (window.preferredDeliveryMethod == "PICKUP") {
            $(".preferred-delivery-pickup").attr("disabled","disabled");
        }
        $('.new-fedex-account').on('click', function () {
            $(".account-heading").text('Add New Account');
            $('.fedex-new-account-form').show();
            $('.add-new-fedex-account').hide();
            $("#btn_payment_save_changes").attr("disabled", true);
            $("#account-number").attr("disabled", false);
            $(".account-error").html("");
            $("#btn_payment_save_changes").removeClass("edit_btn_payment_save_changes");
        });
        $('.action.cancel.primary').on('click', function () {
            $('.fedex-new-account-form').hide();
            $('.add-new-fedex-account').show();
            $('#fedex-account-form').trigger("reset");
            $('.account-error').html('');
            $("#account-number").attr("disabled", false);
            let  containerId = $("#container_id").val();
            if (containerId) {
                $('html, body').animate({
                    scrollTop: $("#"+containerId).offset().top
                }, 1000);
                $("#container_id").val('');
            } else {
                $('html, body').animate({
                    scrollTop: $(".fedex-account-title").offset().top
                }, 1000);
            }
        });
        function validation(accountLength) {
            if (!accountLength) {
                $('.account-error').html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">This field is required.</span>');
                $('.action.submit.primary').attr('disabled','disabled');
            } else {
                $('.account-error').html('');
                $('.action.submit.primary').removeAttr("disabled");
            }
        }
        $(document).on('keyup blur', '#account-number', function () {
            let  accountLength = $(this).val().length;
            console.log(accountLength);
            validation(accountLength);
        });

        $(document).on('keyup blur', '#nickname', function () {
            $(".edit_btn_payment_save_changes").removeAttr("disabled");
        });

        $(document).on('keyup blur', '#billing-reference', function () {
            $(".edit_btn_payment_save_changes").removeAttr("disabled");
        });

        /*B-1210660 Start here*/
        window.onload = function() {
            $(".peferred-delivery-method").removeAttr("disabled");
            if (window.preferredDeliveryMethod == 'PICKUP') {
                $("#pickup-option").trigger('click');
            } else if (window.preferredDeliveryMethod == 'DELIVERY') {
                $("#preferred-delivery-option").trigger('click');
            }
            if (typeof(window.validateFdxLogin) == 'undefined' && window.validateFdxLogin != '' && window.preferredDeliveryMethod != '') {
                shipToAddressPreferredDeliveryMethod();
                if (typeof(window.preferredStore) !== 'undefined') {
                    pickupAtStorePreferredDeliveryMethod();
                }
            }
        }
        /*B-1210660 End here*/

        /*B-1251175 Start here*/
        $("meta[name='viewport']").attr("content", "");
        $("meta[name='viewport']").attr("content", "width=device-width, initial-scale=1");
        $(".nav.item.current").attr("tabindex", "0");
        document.addEventListener("keydown", function(e) {
            if (e.code == "ArrowDown") {
                $(":focus").each(function() {
                    let  _this = this;
                    if ($(_this).parent(".nav.item").length > 0) {
                        if ($(_this).parent(".nav.item").next(".nav.item").children("a").length > 0) {
                            $(_this).parent(".nav.item").next(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).parent(".nav.item").next(".nav.item").children("span").length > 0) {
                            if ($(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("a").length > 0) {
                                $(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("a").trigger('focus');
                            } else if ($(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("strong").length > 0) {
                                $(_this).parent(".nav.item").next(".nav.item").next(".nav.item").trigger('focus');
                            }
                        } else if ($(_this).parent(".nav.item").next(".nav.item").children("strong").length > 0) {
                            $(_this).parent(".nav.item").next(".nav.item").trigger('focus');
                        }
                        e.preventDefault();
                    }
                    if ($(_this).children("strong").length > 0) {
                        $(_this).next(".nav.item").children("a").trigger('focus');
                        e.preventDefault();
                    }
                });
            }
            else if (e.code == "ArrowUp") {
                $(":focus").each(function() {
                    let  _this = this;
                    if ($(_this).parent(".nav.item").length > 0) {
                        if ($(_this).parent(".nav.item").prev(".nav.item").children("a").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).parent(".nav.item").prev(".nav.item").children("span").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).parent(".nav.item").prev(".nav.item").children("strong").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").trigger('focus');
                        }
                        e.preventDefault();
                    }
                    if ($(_this).children("strong").length > 0) {
                        if ($(_this).prev(".nav.item").children("a").length > 0) {
                            $(_this).prev(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).prev(".nav.item").children("span").length > 0) {
                            $(_this).prev(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        }
                        e.preventDefault();
                    }
                });
            }
            else if (e.code == "Enter") {
                $(":focus").each(function() {
                    let  _this = this;
                    if ($(_this).attr("class").length > 0) {
                        if ($(_this).attr("class") == 'radio-label_name') {
                            $(_this).prev(".chk").trigger("click");
                        } else if ($(_this).attr("class") == 'show-details-button') {
                            $(_this).trigger("click");
                        } else if ($(_this).attr("class") == 'account-hide-details-button') {
                            $(_this).trigger("click");
                        } else if ($(_this).attr("class") == 'pick-up-container custom-radio-btn') {
                            $("#pickup-option").trigger('click');
                        }

                    }
                    if ($(_this).attr("id").length > 0) {
                        if ($(_this).attr("id") == 'btn-pickup') {
                            $("#pickup-option").trigger('click');
                        } else if ($(_this).attr("id") == 'btn-delivery') {
                            $("#preferred-delivery-option").trigger('click');
                        } else if ($(_this).attr("id") == 'btn-showmap') {
                            $(".toggle-input").trigger('click');
                        } else if ($(_this).attr("id") == 'btn-remove-zip') {
                            $("#btn-remove-zip").trigger('click');
                        } else if ($(_this).attr("id") == 'succ_msg_close') {
                            $(".img-close-msg").trigger('click');
                        } else if ($(_this).attr("id") == 'err_msg_close') {
                            $(".img-close-msg").trigger('click');
                        }
                    }
                });
            }
        });
        /*B-1251175 End here*/

    });
    /* End FedEx Account Form Validation */
    /* Call profile service to update customer address */
    function profileUpdate() {
        $(".succ-msg").hide();
        $(".err-msg").hide();
        $('button.action-close').trigger('click');
        let  updateProfileUrl =  url.build('fcl/index/updateaccount');
        $.ajax({
            type: "POST",
            url: updateProfileUrl,
            data: [],
            cache: false,
            showLoader: true,
            success: function (data) {
                $('#preferred-delivery-option').trigger('change');
            }
        });
    }
    /* End of Call profile service to update customer address */
    /* Enhanced Profile Edit Popup function */
    $(document).on('click','#preffered-ship-address-update', function() {
        let  options = {
            type: 'popup',
            responsive: true,
            clickableOverlay: false,
            modalClass: 'profile-enhancement-refresh-popup',
            responsiveClass: "modal-slide-disable",
            title: false,
            buttons: [{
                text: $.mage.__('Refresh'),
                class: '',
                click: function () {
                    profileUpdate();
                }
            }]
        };
        let  popup = modal(options, $('.enhanced-profile-popup'));
        let  url = window.profileurl;
        window.open(url, '_blank');
        $('.enhanced-profile-popup').modal(options).modal('openModal');
        $('a.edit-account-link').attr('target','');
        let  popupContent = $('.profile-enhancement-refresh-popup._show .modal-content').html();
        if (!popupContent) {
            let  popupMainContent = $(".enhanced-profile-popup").html();
            $('.profile-enhancement-refresh-popup._show .modal-content').html(popupMainContent);
        }
        /* End of Enhanced Profile Edit Popup function */
    });
    /* End of Enhanced Profile Edit Popup function */

    $(document).on('click', '.preferred-pickup-delivery', function () {

        let  _this = this;
        let  method = $(_this).attr("data-method");

        if (method == 'PICKUP' && $(".chk:checked").length == 0) {
            $(".locatio-err-msg").text("Please select location");
            return false;
        } else {
            $(".locatio-err-msg").text("");
        }

        let  locationId = $(".list-container.selected").find(".pickup-location-id").text();

        let  ajaxUrl =  url.build('customer/account/getdefaultaddress');
        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: {locationId:locationId},
            cache: false,
            showLoader: true,
            success: function (data) {
                $("#preferred-location").html(data);
                $("#preferred-location").show();
                $(".preferred-delivery-pickup").show();
                $(".preferred-pickup-delivery").removeAttr("disabled");
                $("#pickup_save_changes").show();
                $("#pickup_save_changes").removeAttr("disabled");
		$("#pickup_save_change").show();
         	$("#pickup_save_change").removeAttr("disabled");
                $(".map-chooser").hide();
                $(".preferred-store").hide();
                $('html, body').animate({
                    scrollTop: $(".msg-container").offset().top
                }, 500);
            }
        });
    });

    /* End of Enhanced Profile Edit Popup function */

    $(document).on('click', '.preferred-delivery, .remove-prerred-store, .preferred-delivery-pickup', function () {
        let _this = this;
        let method = $(_this).attr("data-method");
        let preferredStore = window.preferredStore;
        if ($(".chk:checked").val()) {
            preferredStore = $(".chk:checked").val();
        }
        let  ajaxUrl =  url.build('customer/account/preferreddeliverymethod');
        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: {userProfileId: window.userProfileId, method: method, preferredStore: preferredStore},
            cache: false,
            showLoader: true,
            success: function (data) {
                if (data === null) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else if (data.Failure || data.errors) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else {
                    if (method == 'PICKUP') {
                        $(".succ-msg").show();
                        $(".succ-msg .message").text("Preferred Store has been successfully added.");
                        $(".err-msg").hide();
                        $(".preferred-delivery").removeAttr("disabled");
                        $(_this).attr("disabled", "disabled");
                        window.preferredDeliveryMethod = 'PICKUP';
                        window.preferredStore = preferredStore;
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500);
                        if(window.e383157Toggle){
                            fxoStorage.set('chosenDeliveryMethod', 'pick-up');
                        }else{
                            localStorage.setItem('chosenDeliveryMethod', 'pick-up');
                        }
                    } else if (method == 'DELIVERY') {
                        $(".succ-msg").show();
                        $(".succ-msg .message").text("Preferred Shipping has been successfully added.");
                        $(".err-msg").hide();
                        $(".preferred-delivery-pickup").removeAttr("disabled");
                        $(_this).attr("disabled", "disabled");
                        window.preferredDeliveryMethod = 'DELIVERY';
                        window.preferredStore = preferredStore;
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500);
                        if(window.e383157Toggle){
                            fxoStorage.set('chosenDeliveryMethod', 'shipping');
                        }else{
                            localStorage.setItem('chosenDeliveryMethod', 'shipping');
                        }
                    }
                }
            }
        });
    });

    $(document).on('click', '.chk', function () {
        if ($(".chk:checked").length > 0) {
            $(".locatio-err-msg").text("");
        }
    });

    $(document).on('change', '.chk', function () {
        let  _this = this;
        let  locationId = $(_this).val();
        if (window.preferredStore != locationId) {
            $(".btn-set-preffered-store-container .preferred-delivery").removeAttr("disabled");
        }
        else if (preferredDeliveryMethod == 'PICKUP' && window.preferredStore == locationId){
            $(".btn-set-preffered-store-container .preferred-delivery").attr("disabled", "disabled");
        }
    });

    $(document).on('click', '.img-close-msg', function () {
        let  _this = this;
        $(_this).parent("div").hide();
    });

    $(document).on('click', '.edit-prerred-store', function () {
        let  zipcode = $('#preferred-location .pickup-location-zipcode').html().trim();
        $('.map-chooser').show();
        $(".preferred-store").show();
        $('#preferred-location').hide();
        $(".preferred-delivery-pickup").hide();
        $('input.zipcodePickup').val(zipcode);
        if(window.e383157Toggle){
            fxoStorage.set("setZipCode",true);
        }else{
            localStorage.setItem("setZipCode",true);
        }
        let enterTriggerEvent = $.Event( "keypress", { which: 13 } );
        $('.zipcodePickup').trigger(enterTriggerEvent);
    });

    $(document).on('click', '.pick-up-container.custom-radio-btn', function () {
        if (!$(".preferred-store-header").length) {
            $("#pickup_save_changes").hide();
        }
    });

});
