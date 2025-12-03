define([
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Fedex_Canva/js/model/canva',
    'Fedex_Canva/js/view/canva',
    'fedex/storage',
    'loader',
    'js/ada',
    'jquery-ui-modules/widget'
], function($, url, customerData, canvaModel, canvaView,fxoStorage) {
    'use strict';

    $.widget('mage.canvaIframe', {
        options: {
            text: null,
            displayEle: null,
            customerData: customerData,
            currentProductSku: null,
            canvaModel: canvaModel,
            debounceIFrameResize: null,
            featureToggle: null,
            tigerE424480Toggle: null
        },
        /**
         * Bind handlers to events
         */
        _create: function() {
            this._handleInitialHeader();
            this._loadCustomerData();

            $(document).on('canva:showCatalog', $.proxy(function () {
                this._showCatalog({});
            }, this));
            this.element.data('options', this.options);
            $(window).on('resize',this._debounceIframeResizeHandler.bind(this));
            if(this.options.featureToggle) {
                $(window).on('popstate', this._handleBrowserBackAction.bind(this));
            }
        },
        _createIframe: function (sections) {
            this.options.canva_section = sections.canva_section;

            if(this.options.tigerE424480Toggle) {
                canvaView.loadCanvaPartnershipSdk(
                    this.options.canva_section.partnershipSdkUrl
                ).then(() => this._loadIframe());
                return;
            }

            const firstScript = document.getElementsByTagName("script")[0];
            this.canvaScript = document.createElement("script");
            this.canvaScript.setAttribute("id", "design-script");
            this.canvaScript.src = this.options.canva_section.partnershipSdkUrl;
            $(this.canvaScript).on('load', $.proxy(this._loadIframe, this));
            firstScript.parentNode.insertBefore(this.canvaScript, firstScript);
        },
        _loadIframe: function () {
            if(this.options.tigerE424480Toggle) {
                canvaView.initializeCanvaPartnershipSdk(
                  this.options.canva_section.clientId,
                  this.options.canva_section.partnerId,
                  this.options.canva_section.userToken,
                  document.getElementById(this.options.container),
                ).then((canvaApi) => this._canvaInitialize(canvaApi));
                return;
            }

            window.Canva.Partnership.initialize({
                apiKey: this.options.canva_section.clientId,
                partnerId: this.options.canva_section.partnerId,
                container: document.getElementById(this.options.container),
                autoAuthToken: this.options.canva_section.userToken
            }).then($.proxy(this._canvaInitialize, this));
        },
        _loadCustomerData: function () {
            customerData.reload(['customer', 'canva_section'], false)
                .then($.proxy(this._createIframe, this));
        },
        _canvaInitialize: function (canva) {
            this.canvaApi = canva;

            let handleCanvaProcess = function () {
                const canvaProductSku = this.options.canvaModel.getSku();
                const queryString = new URLSearchParams(location.search);
                if (queryString.get('process') === 'new' ||
                    (this.options.tigerE424480Toggle && queryString.get('templates') === 'true')
                ) {
                    const urlClean = window.location.href.split("?")[0];
                    history.replaceState(
                        null,
                        document.title,
                        urlClean
                    );
                    sessionStorage.setItem("canva-from-megamenu", true);
                    canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
                    this._showCatalog({});
                    return;
                }
                if(queryString.get('designId') || canvaView.hasEditingState()) {
                    let designIdQueryParam = queryString.get('designId');

                    let designId = this.options.tigerE424480Toggle ?
                        designIdQueryParam || canvaModel.getArtwork()?.designId :
                        designIdQueryParam;

                    if (designId) {
                        this._editDesign(designId);
                        return;
                    }

                    if (canvaView.hasEditingState()) {
                        this._createDesign({
                            partnerProductId: canvaProductSku,
                            designSource: "direct"
                        });
                        return;
                    }
                }

                if(this.options.tigerE424480Toggle &&
                    (queryString.get('createNewDesign') === 'true' || canvaView.hasCreatingState())
                ) {
                    this._createDesign({
                        designSource: "direct",
                        ...canvaModel.getDesignOptions(),
                        onBackClick: () => {
                            window.location.href = sessionStorage.getItem('back-url');
                        }
                    });
                    return;
                }

                if (!_.isEmpty(canvaProductSku)) {
                    this.options.currentProductSku = canvaProductSku;
                    this._createDesign({
                        partnerProductId: canvaProductSku,
                        designSource: "direct"
                    });
                    return;
                }
                sessionStorage.setItem("canva-from-megamenu", true);
                canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
                this._showCatalog({});
            }.bind(this);

            handleCanvaProcess();

        },
        _onProductSelect: function (options) {
            if(this.options.featureToggle) {
                sessionStorage.setItem("canva-edit", "true");
            }
            this.options.canvaModel.setSku(options.partnerProductId);
            this._createDesign(options,  () => {
                this._canvaFromMegaMenuCheck();
            });
        },
        _createDesign: function (options, callback) {
            $(document.body).removeClass('canva-home').addClass('canva-pdp');
            this.canvaApi.createDesign({
                publishLabel: (this.options.featureToggle ? "Review" : "Publish"),
                onArtworkCreate:$.proxy(this._onArtworkCreate, this),
                onBackClick:$.proxy((this.options.featureToggle ? this._backRedirectCanvaHome : this._onBackClick), this),
                onDesignOpen:$.proxy(this._onDesignOpen, this),
                ...options,
            });
            this._setCanvaIFrameHeight();
            if (!_.isEmpty(callback) && (typeof callback === 'function')) {
                callback();
            }
        },
        _showCatalog: function (options) {
            $(document.body).removeClass('canva-pdp').addClass('canva-home');
            this.canvaApi.showCatalog({
                ...options,
                onProductSelect: $.proxy(this._onProductSelect, this),
                onDesignSelect: $.proxy(this._onDesignSelect, this),
                onBackClick:$.proxy(this._onBackClick, this)
            });
            this._setCanvaIFrameHeight();
        },
        _editDesign: function (designId) {
            $(document.body).removeClass('canva-home').addClass('canva-pdp');
            this.canvaApi.editDesign({
                designId: designId,
                publishLabel: (this.options.featureToggle ? "Review" : "Publish"),
                onArtworkCreate:$.proxy(this._onArtworkCreate, this),
                onBackClick:$.proxy((this.options.featureToggle ? this._backRedirectCanvaHome : this._onBackClick), this),
                onDesignOpen:$.proxy((this.options.featureToggle ? this._handleEditFromCanva : this._setCanvaIFrameScrollPosition), this)
            });
            this._setCanvaIFrameHeight();
        },
        _backRedirectCanvaHome: function() {
            sessionStorage.setItem("canvahome-to-editcanva", true);
            document.location.href = url.build('canva') + "?templates=true";
        },
        _onDesignSelect: function (canva) {
            this._editDesign(canva.designId);
        },
        _onArtworkCreate: function (canva) {
            this.options.canvaModel.setArtwork(canva);
            if(window.e383157Toggle){
                fxoStorage.delete('pod-data');
            }else{
                localStorage.removeItem('pod-data');
            }
            window.location = url.build('configurator/index/index');
        },
        _onBackClick: function () {
            if (this.options.currentProductSku) {
                this._createDesign({
                    partnerProductId: this.options.currentProductSku,
                    designSource: "direct"
                });
            }
        },
        _onDesignOpen: function (opts) {

            let setupCanvaEditor = function () {
                this._setCanvaIFrameScrollPosition();
                this.options.canvaModel.setProcess(this.options.canvaModel.process.EDITOR);
                this.options.canvaModel.setArtwork({designId: opts.designId});
                this._setDesignIdOnUrl(opts.designId);
                this._canvaFromMegaMenuCheck();
                if(!this.options.featureToggle) {
                    if(sessionStorage.getItem("canvahome-to-editcanva") === 'true') {
                        window.history.pushState('', null, null);
                        $(window).on('popstate', function() {
                            canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
                            document.location.href = url.build('canva');
                        });
                    } else {
                        window.history.pushState('', null, null);
                        $(window).on('popstate', function() {
                            canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
                            setTimeout(function() {
                                document.location.href = history.go(-2);
                            }, 4000);
                        });
                    }
                }
            }.bind(this);

            canvaView.loginRequest($.proxy(() => {
                setupCanvaEditor()
            }, this));
        },
        _handleEditFromCanva: function (opts) {
            sessionStorage.setItem("canva-edit", "true");
            this._setCanvaIFrameScrollPosition();
            this._setDesignIdOnUrl(opts.designId);
        },
        _handleBrowserBackAction: function () {
            var backToUrl = sessionStorage.getItem('back-url');
            canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, window.location.pathname);
            if(location.href.indexOf('canva/index/index?designId') > -1) {
                setTimeout(() => location.href = $('.action.showcart').attr('href'), 0);
            } else if(sessionStorage.getItem('canva-edit') === "true") {
                sessionStorage.setItem("canva-edit", "false");
                this._backRedirectCanvaHome();
            } else if(backToUrl) {
                sessionStorage.removeItem('back-url');
                setTimeout( () => location.href = backToUrl, 0);
            }
        },
        _setDesignIdOnUrl: function (designId) {
            if(this.options.featureToggle) {
                if(location.href.indexOf('canva/index/index?designId') > -1) {
                    history.pushState(null, document.title, location.href);
                } else {
                    const queryString = new URLSearchParams(location.search);
                    if (!queryString.get('designId')) {
                        if(this.options.tigerE424480Toggle) {
                            const separator = location.search ? '&' : '?';
                            history.pushState(null, document.title, window.location.href + separator + "designId=" + designId);
                        } else {
                            history.pushState(null, document.title, window.location.href + "?designId=" + designId);
                        }
                    }
                }
            } else {
                const queryString = new URLSearchParams(location.search);
                if (!queryString.get('designId')) {
                    history.replaceState(
                        null,
                        document.title,
                        window.location.href + "?designId=" + designId
                    );
                }
            }
        },
        _handleInitialHeader: function () {
            if(sessionStorage.getItem('canva-reload') === 'true') {
                sessionStorage.removeItem('canva-reload');
                if(this.options.tigerE424480Toggle) {
                    location.href = (sessionStorage.getItem('back-url') || url.build('canva'));
                } else {
                    location.href = sessionStorage.getItem('back-url');
                }
            } else if(location.href.indexOf('templates') > -1) {
                sessionStorage.setItem("canva-reload", true);
                if(this.options.tigerE424480Toggle) {
                    // Clear the history state in a canva reload
                    // so we make sure we are not going back to the editor page
                    history.replaceState(null, document.title, window.location.href);
                    canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
                }
            }
            if(sessionStorage.getItem("canva-from-megamenu")) {
                $(document.body).removeClass('canva-pdp').addClass('canva-home');
            }else {
                $(document.body).removeClass('canva-home').addClass('canva-pdp');
            }
        },
        _debounceIframeResizeHandler: function () {
            clearTimeout(this.options.debounceIFrameResize);
            this.options.debounceIFrameResize = setTimeout(() => { this._setCanvaIFrameHeight(); }, 500);
        },
        _setCanvaIFrameHeight: function () {
            var canvaSku;
            if(window.e383157Toggle){
                canvaSku = fxoStorage.get('canva-sku');
            }else{
                canvaSku = window.localStorage.getItem('canva-sku');
            }

            $('.widget-promo-banner-container').hide(); // Promo Banner can't appear on Canva page

            var canvaIFrame = document.querySelector(".canva-iframe iframe");
            let isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
            if(canvaIFrame) {
                canvaIFrame.ariaLabel = 'Canva iFrame';
                if (navigator.userAgent.match(/iPad|iPhone|iPod/i) && isMobile) {
                    var topSectionHeightIOs = canvaIFrame.getBoundingClientRect().top;
                    canvaIFrame.style.height = (window.innerHeight - topSectionHeightIOs) + 'px';
                }
                else {
                    var topSectionHeight = canvaIFrame.getBoundingClientRect().top + 5;
                    canvaIFrame.style.height = 'calc(100vh - ' + topSectionHeight + 'px)';
                }
            }
        },
        _setCanvaIFrameScrollPosition: function () {
            var canvaIframeModal = $(".modal-popup._show");
            $(document.body).removeClass('CHM3WQ');
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            if(canvaIframeModal.length) {
                canvaIframeModal.find('.action-close').trigger('focus');
            }
        },
        _canvaFromMegaMenuCheck: function () {
            if(sessionStorage.getItem("canva-from-megamenu")) {
                $(document).on('canva:login:show', $.proxy(function () {
                    sessionStorage.removeItem("canva-from-megamenu");
                }, this));
            }
        }
    });
    return $.mage.canvaIframe;
});
