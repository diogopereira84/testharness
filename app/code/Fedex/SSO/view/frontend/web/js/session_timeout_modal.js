define([
    'jquery',
        'Magento_Ui/js/modal/modal',
        'mage/url',
        'ajaxUtils',
        'fedex/storage'
], function ($, modal, url, ajaxUtils, fxoStorage) {
    'use strict'

    return function (config, elm) {
        window.sessionTimeOutConfig = {
            session_warning_time: config.session_warning_time,
            profile_logout_time: config.profile_logout_time,
            isEproUser: config.isEproUser,
            isEpro: config.isEpro,
            loggedAsCustomerCustomerId: config.loggedAsCustomerCustomerId,
            mazegeeksCtcAdminImpersonator: config.mazegeeksCtcAdminImpersonator
        };
        var sessionWebWorker,
            fclSessionModal = null,
            modalOptions = null,
            isSessionModalOpen = false,
            sessionWarningTime = 0,
            sessionCountDownTime = 0,
            debounceEventsTimeOut = 0,
            isSdeStore = '0';
        $(document).ready(function () {
            isSdeStore = $('#is_sde_store').val();
            var href = window.location.pathname;

            if (href.indexOf('session-timeout') == -1) {
                logoutHandler();
                setSessionModalInterface();
                registerEvents();
                resetSessionTimeOut();
            }
        });

        function saveSessionTimeoutDate(sessionTimeOut) {
            var sessionTimeOut = window.sessionTimeOutConfig.profile_logout_time;
            var sessionTimeOutDate = new Date().getTime() + sessionTimeOut * 1000;
            sessionStorage.setItem('session-timeout-date', sessionTimeOutDate);
        }

        function isSessionExpired() {
            var sessionTimeOutDate = sessionStorage.getItem('session-timeout-date');
            var currentTime = new Date().getTime();
            if (currentTime > sessionTimeOutDate) {
                return true;
            }
            return false;
        }

        function resetSessionTimeOut() {
            saveSessionTimeoutDate();
            if (sessionWarningTime === 0) {
                var sessionTimeOut = window.sessionTimeOutConfig.profile_logout_time;
                sessionCountDownTime = window.sessionTimeOutConfig.session_warning_time;
                sessionWarningTime = sessionTimeOut - sessionCountDownTime;
                sessionWebWorker = new Worker(require.toUrl('Fedex_SSO/js/session-webworker.js'));
                sessionWebWorker.onmessage = sessionWebWorkerMessage;

                sessionWebWorker.postMessage({
                    command: 'RUN_SESSION_TIMEOUT',
                    sessionWarningTime: sessionWarningTime,
                    sessionCountDownTime: sessionCountDownTime
                });
            } else {
                sessionWebWorker.postMessage({
                    command: 'RESET_SESSION_TIMEOUT'
                });
            }
        }

        function sessionTimedOut() {
            sessionStorage.setItem('fcl-timeout-flag', '1');
            fclSessionModal.modal(modalOptions).modal('closeModal');
            customerLogout();
        }

        function sessionWebWorkerMessage(event) {
            switch (event.data.command) {
                case 'OPEN_SESSION_MODAL':

                    // Different browsers may handle tab inactivity in varied ways and may interfere with the session timer.
                    // We are adding an additional check to see if the session has already expired.
                    // If the session has already expired, we are not opening the session timeout modal, instead we are logging out the user.
                    if (isSessionExpired()) {
                        sessionTimedOut();
                        return;
                    }

                    fclSessionModal.find('.timer').text(event.data.formattedTime);
                    fclSessionModal.removeClass('session-expired');
                    if (!$('.session-modal').hasClass('_show')) {
                        fclSessionModal.modal(modalOptions).modal('openModal');
                    }
                    isSessionModalOpen = true;
                    break;
                case 'UPDATE_SESSION_TIMER':
                    fclSessionModal.find('.timer').text(event.data.formattedTime);
                    break;
                case 'SESSION_TIMED_OUT':
                    sessionTimedOut();
                    break;
            }
        }

        function setSessionModalInterface() {
            fclSessionModal = $('#fcl-session-modal');
            modalOptions = {
                buttons: [],
                modalClass: 'ddt-modal pointer-none session-modal',
                modalCloseBtn: '[data-role=closeBtn]'
            };
            if (sessionStorage.getItem('fcl-timeout-flag')) {
                fclSessionModal.addClass('session-expired')
                    .removeClass('d-none').modal(modalOptions).modal('openModal');
                sessionStorage.removeItem('fcl-timeout-flag');
            } else {
                fclSessionModal.removeClass('d-none').modal(modalOptions);
            }
        }

        function registerEvents() {
            $(document).on('ajaxSend', function (event, jqXHR, ajaxOptions) {
                if (ajaxOptions.url.includes(window.location.host)) {
                    // Its calling a backend endpoint, must reset the session timeout timer.
                    if (!isSessionModalOpen) {
                        resetSessionTimeOut();
                    }
                }
            });
            fclSessionModal.find('.btn-home').on('click', function () {
                location.href = BASE_URL;
            });
            fclSessionModal.on('modalclosed', function () {
                if (!sessionStorage.getItem('fcl-timeout-flag') && isSessionModalOpen) {
                    sessionWebWorker.postMessage({
                        command: 'CLEAR_SESSION_INTERVAL'
                    });
                    var sessionRefreshUrl = url.build('fcl/index/ajaxcallforsessionrefresh');
                    isSessionModalOpen = false;
                    ajaxUtils.get(sessionRefreshUrl, null, false, 'json');
                }
            });
            fclSessionModal.on("timeout", function () {
                sessionTimedOut();
            });
        }

        function logoutHandler() {
            $(document).on('click', '.fxo-logout a', function () {
                commercialCustomerLogout();
            });
        }

        function commercialCustomerLogout() {
            var logoutServiceUrl = url.build('fcl/customer/logout'),
                logoutRedirectUrl = '',
                loginMethod = '',
                redirectCacheClear = Math.random().toString(16).slice(2);
            loginMethod = $('.wlgn-logout a').attr('login-method');
            logoutRedirectUrl = $('.wlgn-logout a').attr('data-url');
            ajaxUtils.get(logoutServiceUrl + "/login_method/" + loginMethod, null, true, 'json', function (data) {
                var isFclLogoutToggleEnabled =true;
                if (window.e383157Toggle) {
                    fxoStorage.clearAll();
                } else {
                    localStorage.clear();
                }
                var loggedAsCustomerCustomerId = window.sessionTimeOutConfig.loggedAsCustomerCustomerId;
                var mazegeeksCtcAdminImpersonator = window.sessionTimeOutConfig.mazegeeksCtcAdminImpersonator;

                if(loggedAsCustomerCustomerId && mazegeeksCtcAdminImpersonator) {
                    loginAsCustomerLogout();
                }
                if ((typeof logoutRedirectUrl !== 'undefined') && (logoutRedirectUrl.length > 0)&&
                    (isFclLogoutToggleEnabled !== 'undefined' && isFclLogoutToggleEnabled == false)) {
                    location.href = logoutRedirectUrl;
                } else {
                    location.href = BASE_URL+'?_='+redirectCacheClear;
                }
            });
        }

        function customerLogout() {
            var logoutServiceUrl = url.build('fcl/customer/logout'),
                logoutRedirectUrl = '',
                redirectCacheClear = Math.random().toString(16).slice(2);
            let isEpro = window.sessionTimeOutConfig.isEpro === '1' ? true : false;

            if (isSdeStore === '1') {
                logoutRedirectUrl = $('#sde_logout_url').val();
            } else {
                logoutRedirectUrl = $('.wlgn-logout a').attr('data-url');
            }

            ajaxUtils.get(logoutServiceUrl, null, true, 'json', function (data) {
                if (window.e383157Toggle) {
                    fxoStorage.clearAll();
                } else {
                    localStorage.clear();
                }
                var loggedAsCustomerCustomerId = window.sessionTimeOutConfig.loggedAsCustomerCustomerId;
                var mazegeeksCtcAdminImpersonator = window.sessionTimeOutConfig.mazegeeksCtcAdminImpersonator;

                if(loggedAsCustomerCustomerId && mazegeeksCtcAdminImpersonator) {
                    loginAsCustomerLogout();
                }
                var href = window.location.pathname;
                var isFclLogoutToggleEnabled =true;

                if (isEpro || (href.indexOf('session-timeout') != -1)) {
                    location.href = window.location.origin + "/session-timeout";
                } else if ((typeof logoutRedirectUrl !== 'undefined') && (logoutRedirectUrl.length > 0)&&
                    (isFclLogoutToggleEnabled !== 'undefined' && isFclLogoutToggleEnabled == false)) {
                    location.href = logoutRedirectUrl;
                } else {
                    location.href = BASE_URL+'?_='+redirectCacheClear;
                }
                

            });
        }

        function loginAsCustomerLogout() {
            var loginAsCustomerlogoutServiceUrl = url.build('fcl/customer/loginAsCustomerlogout');
            $.ajax({
                url: loginAsCustomerlogoutServiceUrl,
                type: 'get',
                showLoader: false,
                success: function (data){
                    if(data) {
                        console.log('logoutSuceess');
                    } else { 
                        console.log('logoutfailed');
                    }
                }
            });
        }
    }
});
