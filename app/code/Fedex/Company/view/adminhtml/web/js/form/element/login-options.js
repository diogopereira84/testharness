define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (_, uiRegistry, select, modal, $) {
    'use strict';

    return select.extend({

        initialize: function () {
            this._super();
            var self = this;

            uiRegistry.async([
                'index = acceptance_option',
                'index = sso_idp',
                'index = sso_group',
                'index = sso_login_url',
                'index = sso_logout_url',
                'index = self_reg_login_method',
                'index = fcl_user_email_verification_error_message',
                'index = fcl_user_email_verification_user_display_message',
                'parentScope = data.authentication_rule, index = domain_name',
                'parentScope = data.authentication_rule, index = network_id',
                'parentScope = data.authentication_rule, index = site_name'
            ])(function (acceptance_option, sso_idp, sso_group, sso_login_url, sso_logout_url,
                          self_reg_login_method, fcl_user_email_verification_error_message,
                          fcl_user_email_verification_user_display_message, domain_name, network_id, site_name) {
                self.field_acceptance_option = acceptance_option;
                self.field_sso_idp = sso_idp;
                self.field_sso_group = sso_group;
                self.field_sso_login_url = sso_login_url;
                self.field_sso_logout_url = sso_logout_url;
                self.field_self_reg_login_method = self_reg_login_method;
                self.field_fcl_user_email_verification_error_message = fcl_user_email_verification_error_message;
                self.field_fcl_user_email_verification_user_display_message = fcl_user_email_verification_user_display_message;
                self.field_domain_name = domain_name;
                self.field_domain_id = network_id;
                self.field_site_name = site_name;

                self.fieldDepend(self);
            });

            return this;
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var field_acceptance_option = this.field_acceptance_option;
            var field_sso_idp = this.field_sso_idp;
            var field_sso_group = this.field_sso_group;
            var field_sso_login_url = this.field_sso_login_url;
            var field_sso_logout_url = this.field_sso_logout_url;
            var field_self_reg_login_method = this.field_self_reg_login_method;
            var field_fcl_user_email_verification_error_message = this.field_fcl_user_email_verification_error_message;
            var field_fcl_user_email_verification_user_display_message = this.field_fcl_user_email_verification_user_display_message;
            var field_domain_name = this.field_domain_name;
            var field_domain_id = this.field_domain_id;
            var field_site_name = this.field_site_name;

            if (value == 'commercial_store_sso' || value == 'commercial_store_sso_with_fcl') {
                if (field_acceptance_option) field_acceptance_option.hide();
                if (field_domain_name) field_domain_name.hide();
                if (field_domain_id) field_domain_id.hide();
                if (field_site_name) field_site_name.hide();
                if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();
                if (field_self_reg_login_method) field_self_reg_login_method.hide();
                if (field_sso_logout_url) field_sso_logout_url.show();
                if (field_sso_login_url) field_sso_login_url.show();
                if (field_sso_idp) field_sso_idp.show();

                if (value == 'commercial_store_sso') {
                    if (field_sso_group) field_sso_group.show();
                } else {
                    if (field_sso_group) field_sso_group.hide();
                }
                $('.auth_rule').hide();

            } else if (value == 'commercial_store_epro') {
                if (field_acceptance_option) field_acceptance_option.show();
                if (field_domain_name) field_domain_name.show();
                if (field_domain_id) field_domain_id.show();
                if (field_site_name) field_site_name.show();
                if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();
                if (field_self_reg_login_method) field_self_reg_login_method.hide();
                if (field_sso_logout_url) field_sso_logout_url.hide();
                if (field_sso_login_url) field_sso_login_url.hide();
                if (field_sso_idp) field_sso_idp.hide();
                if (field_sso_group) field_sso_group.hide();

                var acceptanceOptionValue = $('select[name="authentication_rule[acceptance_option]"]').find(":selected").val();
                if (acceptanceOptionValue == 'contact') {
                    $('.auth_rule.contact').show();
                } else if (acceptanceOptionValue == 'extrinsic') {
                    $('.auth_rule.extrinsic').show();
                } else if (acceptanceOptionValue == 'both') {
                    $('.auth_rule.extrinsic').show();
                    $('.auth_rule.contact').show();
                }

            } else {
                if (value == "commercial_store_wlgn") {
                    if (field_self_reg_login_method) field_self_reg_login_method.show();
                    if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.show();
                    if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.show();
                } else {
                    if (field_self_reg_login_method) field_self_reg_login_method.hide();
                    if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                    if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();
                }

                if (field_acceptance_option) field_acceptance_option.hide();
                if (field_domain_name) field_domain_name.hide();
                if (field_domain_id) field_domain_id.hide();
                if (field_site_name) field_site_name.hide();
                if (field_sso_logout_url) field_sso_logout_url.hide();
                if (field_sso_login_url) field_sso_login_url.hide();
                if (field_sso_idp) field_sso_idp.hide();
                if (field_sso_group) field_sso_group.hide();
                $('.auth_rule').hide();
            }

            return this._super();
        },

        fieldDepend: function (self) {
            $(document).ready(function () {
                var value = self.value();

                var field_acceptance_option = self.field_acceptance_option;
                var field_sso_idp = self.field_sso_idp;
                var field_sso_group = self.field_sso_group;
                var field_sso_login_url = self.field_sso_login_url;
                var field_sso_logout_url = self.field_sso_logout_url;
                var field_self_reg_login_method = self.field_self_reg_login_method;
                var field_fcl_user_email_verification_error_message = self.field_fcl_user_email_verification_error_message;
                var field_fcl_user_email_verification_user_display_message = self.field_fcl_user_email_verification_user_display_message;
                var field_domain_name = self.field_domain_name;
                var field_domain_id = self.field_domain_id;
                var field_site_name = self.field_site_name;

                if (value == 'commercial_store_sso' || value == 'commercial_store_sso_with_fcl') {
                    if (field_acceptance_option) field_acceptance_option.hide();
                    if (field_domain_name) field_domain_name.hide();
                    if (field_domain_id) field_domain_id.hide();
                    if (field_site_name) field_site_name.hide();
                    if (field_self_reg_login_method) field_self_reg_login_method.hide();
                    if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                    if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();
                    if (field_sso_logout_url) field_sso_logout_url.show();
                    if (field_sso_login_url) field_sso_login_url.show();
                    if (field_sso_idp) field_sso_idp.show();

                    if (value == 'commercial_store_sso') {
                        if (field_sso_group) field_sso_group.show();
                    } else {
                        if (field_sso_group) field_sso_group.hide();
                    }
                    $('.auth_rule').hide();

                } else if (value == 'commercial_store_epro') {
                    if (field_acceptance_option) field_acceptance_option.show();
                    if (field_domain_name) field_domain_name.show();
                    if (field_domain_id) field_domain_id.show();
                    if (field_site_name) field_site_name.show();
                    if (field_sso_logout_url) field_sso_logout_url.hide();
                    if (field_self_reg_login_method) field_self_reg_login_method.hide();
                    if (field_sso_login_url) field_sso_login_url.hide();
                    if (field_sso_idp) field_sso_idp.hide();
                    if (field_sso_group) field_sso_group.hide();
                    if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                    if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();

                } else {
                    if (value == "commercial_store_wlgn") {
                        if (field_self_reg_login_method) field_self_reg_login_method.show();
                        if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.show();
                        if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.show();
                    } else {
                        if (field_self_reg_login_method) field_self_reg_login_method.hide();
                        if (field_fcl_user_email_verification_error_message) field_fcl_user_email_verification_error_message.hide();
                        if (field_fcl_user_email_verification_user_display_message) field_fcl_user_email_verification_user_display_message.hide();
                    }

                    if (field_acceptance_option) field_acceptance_option.hide();
                    if (field_domain_name) field_domain_name.hide();
                    if (field_domain_id) field_domain_id.hide();
                    if (field_site_name) field_site_name.hide();
                    if (field_sso_logout_url) field_sso_logout_url.hide();
                    if (field_sso_login_url) field_sso_login_url.hide();
                    if (field_sso_idp) field_sso_idp.hide();
                    if (field_sso_group) field_sso_group.hide();
                    $('.auth_rule').hide();
                }
            });
        }

    });
});
