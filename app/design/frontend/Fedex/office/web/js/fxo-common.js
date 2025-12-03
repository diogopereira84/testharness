 /**
 * fxo-common.js is loaded in all pages.
 * Scripts that need to be executed across the application should go inside this.
 */

require(['jquery', 'underscore', 'product', 'ajaxUtils', 'utils', 'domReady!'], function ($, _, product, ajaxUtils, jsutils) {
    'use strict';

    window.patternFlyTruncation = jsutils.patternFlyTruncation;

    //Quick Live Search and ADA Starts Here
    window.openIframe = product;
    let hasFocusFlag = false;
    const body = $('body');
    const quickSearch = $('.quick-search');
    const searchInput = quickSearch.find('.search-input');
    const searchSuggestions = quickSearch.find('.custom-search');
    const popularSearch = quickSearch.find('.popular-search');
    const THRID_PARTY_PAGE_LAYOUT = 'third-party-product-full-width';
    const PRINT_PRODUCTS_CUSTOMER_GROUP = '-1';
    const BUNDLE_PRODUCT_TYPE = 'BundleProduct';

    window.LiveSearchParameters.isRetailFlow = quickSearch.hasClass('retail-search') ? true : false;

    $('.btn-toggle-search').on('click',function(event) {
        const $navSections = $('.nav-sections');
        $($navSections).toggleClass('search-open');

        if($($navSections).hasClass('search-open')) {
            searchInput.trigger('focus');
        }
    });
    searchInput.on('focus', function () {
        body.on('click', onBodyClickHandler);
        if(this.value.trim().length === 0) {
            popularSearch.addClass('d-block').attr('aria-hidden', 'false');
        }
    });
    searchInput.on('input', _.debounce(function() {
        popularSearch.removeClass('d-block').attr('aria-hidden', 'true');
        const searchText = this.value.trim();
        if (searchText.length >= 3) {
            productSearch(searchText);
        } else {
            searchSuggestions.removeClass('d-block').attr('aria-hidden', 'true');
        }
    }, 500));
    quickSearch.find('.btn-view-srp').on('click',function(e) {
        e.preventDefault();
        viewAllProducts();
    });
    quickSearch.find('.search-field').on('keydown', function(e) {
        let firstSuggestion = null;
        let nextSuggestion = null;
        const currentSuggestion = $(e.target);
        switch (e.key) {
            case 'ArrowDown':
                if(e.target.className.indexOf('search-input') > -1) {
                    firstSuggestion = quickSearch.find('.search-suggestions.d-block .result-list .product-result:first-child');
                } else {
                    nextSuggestion = currentSuggestion.next();
                }
            break;
            case 'ArrowUp':
                if(e.target.className.indexOf('product-result') > -1) {
                    nextSuggestion = currentSuggestion.prev();
                }
            break;
            case 'Enter':
                if(e.target.className.indexOf('search-input') > -1) {
                    viewAllProducts();
                }
            break;
        }
        if(firstSuggestion) {
            e.preventDefault();
            firstSuggestion.attr('aria-selected', 'true').trigger('focus');
            body.addClass('_keyfocus');
            hasFocusFlag = true;
        } else if(nextSuggestion) {
            e.preventDefault();
            currentSuggestion.attr('aria-selected', 'false');
            nextSuggestion.attr('aria-selected', 'true').trigger('focus');
        }
    });
    if(!window.LiveSearchParameters.isRetailFlow) {
        searchSuggestions.on('click', '.product-result', function(e) {
            // if the link has data-commercial-third-party attribute, then it is a third party product
            // we should not do anything on click, just let the link to go to the product page
            const productCTA = $(this).find('.product-cta');
            const productAction = productCTA.data('action');
            const productIdentifier = productCTA.data('productidentifier');
            const productType = productCTA.data('product-type');
            if (productAction === 'view' || $(this).data("commercial-third-party")) {
                return;
            } else if(productAction === 'upload') {
                openIframe.uploadPrintProduct(e)(productIdentifier, window.LiveSearchParameters.siteName, window.LiveSearchParameters.TazToken);
            } else if(productAction === 'customize') {
                openIframe.uploadCustomDocProduct(e)(window.LiveSearchParameters.TazToken, window.LiveSearchParameters.siteName, productIdentifier);
            } else {
                if(productType === BUNDLE_PRODUCT_TYPE) {
                    openIframe.addBundleProductToCart(e)(productIdentifier);
                } else {
                    openIframe.addProductToCart(e)(productIdentifier);
                }
            }
        });
    }
    function onBodyClickHandler() {
        if(!quickSearch.has(document.activeElement).length) {
            quickSearch.find('.search-suggestions.d-block').removeClass('d-block').attr('aria-hidden', 'true');
            body.off('click', onBodyClickHandler);
        }
        if(hasFocusFlag) {
            body.removeClass('_keyfocus');
            hasFocusFlag = false;
        }
    }
    function productSearch(searchText) {
        if(window.LiveSearchParameters) {
            let attributeParams = '';
            const searchHeaders = {
                "Content-Type": "application/json",
                "X-Api-Key": window.LiveSearchParameters.XApiKey,
                "Magento-Environment-Id": window.LiveSearchParameters.environmentId,
                "Magento-Store-Code": window.LiveSearchParameters.storeCode,
                "Magento-Store-View-Code": window.LiveSearchParameters.storeViewCode,
                "Magento-Website-Code": window.LiveSearchParameters.websiteCode,
                "X-Request-Id": generateRequestId()
            };
            if(!window.LiveSearchParameters.isRetailFlow || window.LiveSearchParameters.displayUnitCost3p1pProductsToggle) {
                attributeParams=`productView {
                    attributes {
                        label
                        name
                        value
                    }
                }`;
            }

            const shouldDisplayOnlyPrintOnDemandProducts = !window.LiveSearchParameters.isRetailFlow &&
                !window.LiveSearchParameters.allowOwnDocument &&
                window.checkout?.tiger_team_d_217182;

            const sharedCatalogIdsInString = window.LiveSearchParameters.sharedCatalogId ?
                window.LiveSearchParameters.sharedCatalogId.map(id => `"${id}"`).join(',') : '';

            const allowedCustomerGroupsFilter = window.LiveSearchParameters.isTigerD200529Enabled && !window.LiveSearchParameters.isRetailFlow
                ? `{ attribute: "allowed_customer_groups", in: ["${PRINT_PRODUCTS_CUSTOMER_GROUP}", "${window.LiveSearchParameters.customerGroupId}"] }`
                : '';

            const shareCatalogIdFilter = `filter: [{
                attribute: "shared_catalogs"
                in: [${sharedCatalogIdsInString}]
            },
            {
                attribute: "is_pending_review"
                in: ["0","2","3"]
            }
            ${allowedCustomerGroupsFilter? `,${allowedCustomerGroupsFilter}` : ''}
            ${shouldDisplayOnlyPrintOnDemandProducts ? `, { attribute: "product_attribute_sets_id", in: ["${window.LiveSearchParameters.attributeSets.PrintOnDemand}"] }` : ''}
            ${!window.LiveSearchParameters?.isRetailFlow && window.LiveSearchParameters?.hideUnpublishedInSearch ? ',{attribute: "published", eq: "1"}' : ''}]`;

            const contextFilter = `context: { customerGroup: "${window.LiveSearchParameters.customerGroup}" }`;

            const searchPayLoad = {
                "query": `query productSearch {
                    productSearch(
                        ${window.LiveSearchParameters.sharedCatalogId ? shareCatalogIdFilter : contextFilter }
                        phrase: "${searchText}"
                        page_size: 10
                    )
                    {
                        total_count
                        items {
                            product {
                                id
                                sku
                                name
                                __typename
                                canonical_url
                                __typename
                                small_image {
                                    url
                                }
                                image {
                                    url
                                    label
                                }
                                thumbnail {
                                    url
                                    label
                                }
                                price_range {
                                    minimum_price {
                                        final_price {
                                            value
                                            currency
                                        }
                                    }
                                }
                            }
                            ${attributeParams}
                        }
                        facets {
                            title
                        }
                    }
                }`,
                operationName: "productSearch"
            };
            ajaxUtils.post(window.LiveSearchParameters.serviceUrl, searchHeaders, JSON.stringify(searchPayLoad), false, 'json', populateProductList);
        }
    }
    function productItemPrice(price, unitCost) {
        if(window.LiveSearchParameters.displayUnitCost3p1pProductsToggle) {
          if (Number(unitCost) > 0) {
            return $(`<span class="product-price cl-gray d-none visible-m-l-xl fedex-light ml-auto text-right">Starting at $${unitCost} each</span>`);
          }
          return $(`<span class="product-price cl-gray d-none visible-m-l-xl fedex-light ml-auto text-right">Starting at $${price} each</span>`);
        }
        return $(`<span class="product-price cl-gray d-none visible-m-l-xl fedex-light ml-auto text-right">Starting at $${price}</span>`);
    }

    function populateProductList(searchResponse) {
        const productSearch = searchResponse.data.productSearch;
        if(productSearch.total_count) {
            const productItemsCount = productSearch.items.length,
                $productList = searchSuggestions.find('.product-list'),
                viewAllLink = searchSuggestions.find('.view-all');
            let item = null,
                $productItem = null,
                productImgUrl = '',
                $productImage = null,
                $productName = null,
                $productPrice = null,
                $productCTA = null,
                attributeCount = 0,
                productAttribute = null,
                commercialAttributes = {};
            $productList.empty();
            for(let itemIndex=0; itemIndex < productItemsCount; itemIndex++) {
                item = productSearch.items[itemIndex];
                commercialAttributes = {
                    isDyeSubProduct: false,
                    uploadAction: false,
                    customizable: false,
                    customizeSearchAction: false,
                    catalogProductID: ''
                };
                let page_layout_search = '';
                let productLayoutAttribute = item.productView?.attributes?.find(
                  (attribute) => attribute.name === "page_layout_search"
                );
                if (productLayoutAttribute) {
                  page_layout_search = productLayoutAttribute.value;
                }
                const isCommercial3rdParty = page_layout_search === THRID_PARTY_PAGE_LAYOUT;
                const productImgLabel = item.product?.image?.label || item.product?.thumbnail?.label || item.product?.name || '';
                $productItem = $(`<a class="product-result d-flex fs-16 lh-24 no-underline v-center" href="${item.product.canonical_url}" role="option" aria-selected="false" data-commercial-third-party=${isCommercial3rdParty}></a>`);
                productImgUrl = item.product.thumbnail && item.product.thumbnail.url ? item.product.thumbnail.url : window.LiveSearchParameters.placeholderImage;
                $productImage = $(`<img src="${productImgUrl}" class="product-image mr-15" alt="${productImgLabel}">`);
                $productName = $(`<span class="product-name cl-gray">${item.product.name}</span>`);
                $productItem.append($productImage).append($productName);
                if(window.LiveSearchParameters.isRetailFlow || isCommercial3rdParty) {
                    const price = item.product?.price_range?.minimum_price?.final_price?.value?.toFixed(2) || 'NaN';
                    const unitCostAttribute = item.productView?.attributes.find(attr => attr.name == 'unit_cost')
                    const unitCost = unitCostAttribute?.value ? parseFloat(unitCostAttribute?.value).toFixed(2) : null;
                    $productPrice = productItemPrice(price, unitCost)
                    $productItem.append($productPrice);
                } else {
                    attributeCount = item.productView.attributes.length;

                    if ( window.LiveSearchParameters?.isEssendantToggleEnabled ) {
                        let attrSetIdAttr, externalProdAttr, customizableAttr, isDyeSubFlag = false;

                        for (const attr of item.productView.attributes) {
                            if (window.tiger_E_478196_dye_sub_pod_2_updates && attr.name === 'is_customer_canvas' && attr.value === '1') {
                                isDyeSubFlag = true;
                            } else if (attr.name === 'product_attribute_sets_id') {
                                attrSetIdAttr = attr.value;
                            } else if (attr.name === 'external_prod') {
                                externalProdAttr = attr.value;
                            } else if (attr.name === 'customizable') {
                                customizableAttr = attr.value;
                            }

                            if (
                                (attrSetIdAttr && externalProdAttr && customizableAttr) ||
                                (attrSetIdAttr === window.LiveSearchParameters?.attributeSets?.FXOPrintProducts)
                            ) break;
                        }

                        if(isDyeSubFlag && (attrSetIdAttr !== window.LiveSearchParameters?.attributeSets?.PrintOnDemand)) {
                            commercialAttributes.isDyeSubProduct = true;
                        } else if ( attrSetIdAttr === window.LiveSearchParameters?.attributeSets?.FXOPrintProducts && item.product.__typename !== BUNDLE_PRODUCT_TYPE ) {
                            commercialAttributes.uploadAction = true;
                        } else if ( attrSetIdAttr === window.LiveSearchParameters?.attributeSets?.PrintOnDemand && customizableAttr === 'yes' ) {
                            commercialAttributes.customizeSearchAction = true;
                            commercialAttributes.customizable = true;
                            if (externalProdAttr) {
                                try {
                                    commercialAttributes.catalogProductID = JSON.parse(externalProdAttr)?.catalogReference?.catalogProductId;
                                } catch (error) {
                                    commercialAttributes.catalogProductID = "";
                                }
                            }
                        }

                    } else {
                        for(let attributeIndex=0; attributeIndex < attributeCount; attributeIndex++) {
                            productAttribute = item.productView.attributes[attributeIndex];
                            if (window.tiger_E_478196_dye_sub_pod_2_updates && productAttribute.name === 'is_customer_canvas' && productAttribute.value === '1') {
                                commercialAttributes.isDyeSubProduct = true;
                                break;
                            } else if(productAttribute.name === 'upload_file_search_action' && productAttribute.value === 'yes') {
                                commercialAttributes.uploadAction = true;
                                break;
                            } else if(productAttribute.name === 'customizable' && productAttribute.value === 'yes') {
                                commercialAttributes.customizable = true;
                            } else if(productAttribute.name === 'customize_search_action' && productAttribute.value === 'yes') {
                                commercialAttributes.customizeSearchAction = true;
                            } else if(productAttribute.name === 'external_prod') {
                                try {
                                    commercialAttributes.catalogProductID = JSON.parse(productAttribute.value)?.catalogReference?.catalogProductId;
                                } catch (error) {
                                    commercialAttributes.catalogProductID = "";
                                }
                            }
                        }
                    }

                    if(commercialAttributes.isDyeSubProduct) {
                        $productCTA = $(`<span data-action="view" class="product-cta cl-digital-blue fs-14 fedex-bold ls-1 d-none visible-m-l-xl ml-auto">VIEW DETAILS</span>`);
                    } else if(commercialAttributes.uploadAction) {
                        $productCTA = $(`<span data-productidentifier="${item.product.sku}" data-action="upload" class="product-cta cl-digital-blue fs-14 fedex-bold ls-1 d-none visible-m-l-xl ml-auto">UPLOAD FILE</span>`);
                    } else if(commercialAttributes.customizable && commercialAttributes.customizeSearchAction) {

                        // If its a custozmiable product and customize action is enabled, then we need to use the catalogProductID
                        // as long as the siteName is available and the catalogProductID is available.
                        // Otherwise, we need to use the product sku as the product identifier.
                        if(!window.LiveSearchParameters.siteName || !commercialAttributes.catalogProductID) {
                            $productCTA = $(`<span data-productidentifier="${item.product.sku}" data-action="customize" class="product-cta cl-digital-blue fs-14 fedex-bold ls-1 d-none visible-m-l-xl ml-auto">CUSTOMIZE</span>`);
                        } else {
                            $productCTA = $(`<span data-productidentifier="${commercialAttributes.catalogProductID}" data-action="customize" class="product-cta cl-digital-blue fs-14 fedex-bold ls-1 d-none visible-m-l-xl ml-auto">CUSTOMIZE</span>`);
                        }
                    } else {
                        let productType = item.product.__typename;
                        $productCTA = $(`<span data-productidentifier="${item.product.sku}" data-action="addtocart" data-product-type="${productType}" class="product-cta cl-digital-blue fs-14 fedex-bold ls-1 d-none visible-m-l-xl ml-auto">ADD TO CART</span>`);
                    }

                    // Check if 'is_unavailable' attribute exists with value 'yes' and add a new class
                    const isUnavailable = window.LiveSearchParameters.isE441563ToggleEnabled &&
                    item.productView?.attributes.some(
                        (attr) => attr.name === 'is_unavailable' && attr.value === 'yes'
                    );
                    if (isUnavailable) {
                        $productItem.addClass('unavailable');
                    }
                    $productItem.append($productCTA);
                }
                $productList.append($productItem);
            }
            if(productSearch.total_count > 10) {
                const viewAllURL = viewAllLink.data('url') + searchInput.val().trim();
                viewAllLink.attr('href', viewAllURL).addClass('d-block');
            } else {
                viewAllLink.removeClass('d-block');
            }
            searchSuggestions.addClass('d-block').attr('aria-hidden', 'false');
        } else {
            searchSuggestions.removeClass('d-block').attr('aria-hidden', 'true');
        }
    }
    //This generateRequestId method is taken from Adobe Live Search module
    function generateRequestId(e, t, r) {
        function At() {
            let Ot;
            const Dt = new Uint8Array(16);
            if (!Ot && (Ot = "undefined" != typeof crypto && crypto.getRandomValues && crypto.getRandomValues.bind(crypto), !Ot)) throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");
            return Ot(Dt)
        }
        const jt = [];
        for (let e = 0; e < 256; ++e) jt.push((e + 256).toString(16).slice(1));

        function Ut(e, t = 0) {
            return (jt[e[t + 0]] + jt[e[t + 1]] + jt[e[t + 2]] + jt[e[t + 3]] + "-" + jt[e[t + 4]] + jt[e[t + 5]] + "-" + jt[e[t + 6]] + jt[e[t + 7]] + "-" + jt[e[t + 8]] + jt[e[t + 9]] + "-" + jt[e[t + 10]] + jt[e[t + 11]] + jt[e[t + 12]] + jt[e[t + 13]] + jt[e[t + 14]] + jt[e[t + 15]]).toLowerCase()
        }
        const Tt = {
            randomUUID: "undefined" != typeof crypto && crypto.randomUUID && crypto.randomUUID.bind(crypto)
        };
        if (Tt.randomUUID && !t && !e) return Tt.randomUUID();
        const n = (e = e || {}).random || (e.rng || At)();
        if (n[6] = 15 & n[6] | 64, n[8] = 63 & n[8] | 128, t) {
            r = r || 0;
            for (let e = 0; e < 16; ++e) t[r + e] = n[e];
            return t
        }
        return Ut(n)
    }
    function viewAllProducts() {
        const searchTerm = searchInput.val().trim();
        if(searchTerm.length) {
            location.href = searchSuggestions.find('.view-all').data('url') + searchTerm;
        }
    }
    $(document).on('click', '.close-message', function() {
        $(this).closest('.message-block').fadeOut("normal", function() {
            $(this).remove();
        });
    });
    //Quick Live Search and ADA Ends Here

    if(window.browser_version_check.enable) {
        const browserNotificationRetail = localStorage.getItem('browserNotificationRetail');
        const browserNotificationCommercial = localStorage.getItem('browserNotificationCommercial');
        if (!browserNotificationRetail || (window?.checkout?.is_commercial && !browserNotificationCommercial)) {
            checkBrowserVersion();
        }
    }
    function checkBrowserVersion() {
        const browserBaselineVersions = {
            'chrome': window.browser_version_check.chrome_minimum_version,
            'firefox': window.browser_version_check.firefox_minimum_version,
            'safari': window.browser_version_check.safari_minimum_version,
            'edge': window.browser_version_check.edge_minimum_version
        };
        const browserUserAgent = navigator.userAgent;
        let browserName = '';
        let browserVersion = 0;
        if (/opera|opr|wow64|msie|trident|samsungbrowser|yabrowser|brave|ucbrowser/i.test(browserUserAgent)) {
            // This check is needed as the UA string of these browsers contain names like chrome & safari.
            return;
        } else if (/edg/i.test(browserUserAgent)) {
            browserName = 'edge';
            browserVersion = parseInt(browserUserAgent.match(/(edg|edga|edge)\/(\d+)\./i)[2]);
        } else if (/firefox|fxios/i.test(browserUserAgent)) {
            browserName = 'firefox';
            browserVersion = parseInt(browserUserAgent.match(/(firefox|fxios)\/(\d+)\./i)[2]);
        } else if (/chrome|crios|crmo/i.test(browserUserAgent)) {
            browserName = 'chrome';
            browserVersion = parseInt(browserUserAgent.match(/(chrome|crios|crmo)\/(\d+)\./i)[2]);
        } else if (/safari/i.test(browserUserAgent)) {
            browserName = 'safari';
            let versionMatch = browserUserAgent.match(/version\/(\d+)\./i);
            if (versionMatch && versionMatch[1]) {
                browserVersion = parseInt(versionMatch[1]);
            } else {
                versionMatch = browserUserAgent.match(/OS (\d+)_/i);
                if (versionMatch && versionMatch[1]) {
                    browserVersion = parseInt(versionMatch[1]);
                } else {
                    // fallback: unknown Safari version
                    browserVersion = 0;
                } 
            }
        }
        if(browserName.length) {
            const browserBaselineVersion = parseInt(browserBaselineVersions[browserName]);
            if (browserVersion < browserBaselineVersion) {
                const $browserNotification = $('.browser-notification');
                $browserNotification.find('.message-title').text(window.browser_version_check.heading);
                $browserNotification.find('.message-details').text(window.browser_version_check.subheading);
                $browserNotification.removeClass('hide');
                localStorage.setItem('browserNotificationRetail', 'true');
                if(window?.checkout?.is_commercial) {
                    localStorage.setItem('browserNotificationCommercial', 'true');
                }
            }
        }
    }
});
