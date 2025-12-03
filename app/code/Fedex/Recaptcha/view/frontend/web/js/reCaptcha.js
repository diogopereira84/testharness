/* global grecaptcha */
define(
    [
        'ko',
        "jquery",
        'underscore',
        'Magento_ReCaptchaFrontendUi/js/registry',
        'Magento_ReCaptchaFrontendUi/js/reCaptchaScriptLoader',
        'Magento_ReCaptchaFrontendUi/js/nonInlineReCaptchaRenderer'
    ], function (ko, $, _, registry, reCaptchaLoader, nonInlineReCaptchaRenderer) {
        'use strict';

        return {
            defaults: {
                invisible: true,
                reCaptchaId: crypto.randomUUID(),
                token: null,
                rendering: {
                    sitekey: '',
                    badge: 'inline',
                    size: 'invisible',
                    theme: 'light',
                    hl: null
                },
                widgetId: false
            },

            captchaInitialized: ko.observable(false),
            /**
             * Render reCAPTCHA
             */
            renderReCaptcha: function (elementId) {
                    this.setUpSiteKey();
                    if (this.defaults.rendering.sitekey) {
                        this._generateRecaptchaHtml(elementId);
                        this._loadApi();
                        if (window.grecaptcha && window.grecaptcha.render) { // Check if reCAPTCHA is already loaded
                            this.initCaptcha(elementId);
                        } else { // Wait for reCAPTCHA to be loaded
                            $(window).on('recaptchaapiready', function () {
                                this.initCaptcha(elementId);
                            }.bind(this));
                        }
                    }
            },

            setUpSiteKey: function () {
                this.defaults.rendering.sitekey = typeof (window.checkout.tiger_google_recaptcha_site_key) !== "undefined" && window.checkout.tiger_google_recaptcha_site_key !== null
                    ? window.checkout.tiger_google_recaptcha_site_key
                    : (typeof (window.checkoutConfig.tiger_google_recaptcha_site_key) !== "undefined" && window.checkoutConfig.tiger_google_recaptcha_site_key !== null
                        ? window.checkoutConfig.tiger_google_recaptcha_site_key : false);
            },

            _generateRecaptchaHtml: function (elementId) {
                let hash = this.defaults.reCaptchaId;
                let parentForm = $('#'+elementId);
                parentForm.append(
                    '<div class="field-recaptcha" id="' + hash + '-container" data-bind="scope:\''+hash+'\'">' +
                        '<div id="' + hash + '-wrapper">' +
                            '<div class="g-recaptcha"></div>' +
                        '</div>' +
                    '</div>'
                )
            },

            /**
             * Loads recaptchaapi API and triggers event, when loaded
             * @private
             */
            _loadApi: function () {
                if (this._isApiRegistered !== undefined) {
                    if (this._isApiRegistered === true) {
                        $(window).trigger('recaptchaapiready');
                    }

                    return;
                }
                this._isApiRegistered = false;

                // global function
                window.globalOnRecaptchaOnLoadCallback = function () {
                    this._isApiRegistered = true;
                    $(window).trigger('recaptchaapiready');
                }.bind(this);

                reCaptchaLoader.addReCaptchaScriptTag();
            },

            /**
             * Checking that reCAPTCHA is invisible type
             * @returns {Boolean}
             */
            getIsInvisibleRecaptcha: function () {
                if (this.defaults ===

                    void 0) {
                    return false;
                }

                return this.defaults.invisible;
            },

            /**
             * reCAPTCHA callback
             * @param {String} token
             */
            reCaptchaCallback: function (token) {
                if (this.getIsInvisibleRecaptcha()) {
                    let self = this;
                    self.tokenField.value = token;
                    var interval = setInterval(function(){
                        grecaptcha.execute(self.defaults.widgetId).then(function(token){
                            self.defaults.token = token;
                            clearInterval(interval);
                        });
                    }, 110000)
                }
            },

            /**
             * Initialize reCAPTCHA after first rendering
             */
            initCaptcha: function (elementId) {
                var $parentForm,
                    $wrapper,
                    $reCaptcha,
                    widgetId,
                    parameters,
                    hash;

                if (this.captchaInitialized() || this.defaults ===

                    void 0) {
                    return;
                }

                this.captchaInitialized(true);

                hash = this.defaults.reCaptchaId;
                $wrapper = $('#' + hash + '-wrapper');
                $reCaptcha = $wrapper.find('.g-recaptcha');
                $reCaptcha.attr('id', hash);
                $parentForm = $('#' + elementId);

                if (this.defaults === undefined) {

                    return;
                }

                parameters = _.extend(
                    {
                        'callback': function (token) { // jscs:ignore jsDoc
                            this.reCaptchaCallback(token);
                            this.validateReCaptcha(true);
                        }.bind(this),
                        'expired-callback': function () {
                            this.validateReCaptcha(false);
                        }.bind(this)
                    },
                    this.defaults.rendering
                );

                if (parameters.size === 'invisible' && parameters.badge !== 'inline') {
                    nonInlineReCaptchaRenderer.add($reCaptcha, parameters);
                }

                // eslint-disable-next-line no-undef
                widgetId = grecaptcha.render(hash, parameters);
                this.defaults.widgetId = widgetId;
                this.initParentForm($parentForm, widgetId);

                registry.ids.push(hash);
                registry.captchaList.push(widgetId);
                registry.tokenFields.push(this.tokenField);

            },

            /**
             * Initialize parent form.
             *
             * @param {Object} parentForm
             * @param {String} widgetId
             */
            initParentForm: function (parentForm, widgetId) {
                var listeners;

                if (this.getIsInvisibleRecaptcha() && parentForm.length > 0) {
                    parentForm.submit(function (event) {
                        if (!this.tokenField.value) {
                            // eslint-disable-next-line no-undef
                            grecaptcha.execute(widgetId);
                            event.preventDefault(event);
                            event.stopImmediatePropagation();
                        }
                    }.bind(this));

                    // Move our (last) handler topmost. We need this to avoid submit bindings with ko.
                    listeners = $._data(parentForm[0], 'events').submit;
                    listeners.unshift(listeners.pop());

                    // Create a virtual token field
                    this.tokenField = $('<input type="text" id="token-' + this.defaults.reCaptchaId +'" name="token" style="display: none" />')[0];
                    this.$parentForm = parentForm;
                    parentForm.append(this.tokenField);
                } else {
                    this.tokenField = null;
                }
                if ($('#send2').length > 0) {$('#send2').prop('disabled', false);}
            },

            /**
             * Validates reCAPTCHA
             * @param {*} state
             * @returns {jQuery}
             */
            validateReCaptcha: function (state) {
                if (!this.getIsInvisibleRecaptcha()) {
                    return $(document).find('input[type=checkbox].required-captcha').prop('checked', state);
                }
            },

            /**
             * Get reCAPTCHA ID
             * @returns {String}
             */
            getReCaptchaId: function () {
                return this.defaults.reCaptchaId;
            },

            executeReCaptcha: function (actionName = null) {
                let self = this;
                return new Promise((resolve, reject) => {
                    grecaptcha.execute(self.defaults.widgetId, {action: actionName}).then(function (token) {
                        self.defaults.token = token;
                        resolve(token);
                    });
                })
            },

            getReCaptchaToken: function () {
                var tokenField = $('#token-'+this.defaults.reCaptchaId);

                if (tokenField.length) {
                    return tokenField.val();
                }

                return '';
            },

            /**
             * Refactored getRecaptchaToken function
             * @param {String} actionName
             * @returns {String}
             */
            generateRecaptchaToken: async function (actionName) {
                let recaptchaToken;
                if(window.d_231886_recaptcha_errors){
                    recaptchaToken = await this.executeReCaptcha(actionName);
                }else{
                    await this.executeReCaptcha(actionName);
                    recaptchaToken = this.getReCaptchaToken();
                }
                return recaptchaToken;
            },

            addRecaptchaTokenToPayload: async function (payload, actionName) {
                if (window?.recaptchaSettings !== null && window.recaptchaSettings.hasOwnProperty(actionName) && window.recaptchaSettings[actionName] !== null) {
                    payload['g-recaptcha-response'] = await this.generateRecaptchaToken(actionName);
                }
            },

            waitForCaptchaInitialized: async function() {
                if (this.captchaInitialized()) {
                    return;
                }

                return new Promise((resolve) => {
                    const subscription = this.captchaInitialized.subscribe((loaded) => {
                        if (loaded) {
                            subscription.dispose();
                            resolve();
                        }
                    });
                });
            }
        }
    });
