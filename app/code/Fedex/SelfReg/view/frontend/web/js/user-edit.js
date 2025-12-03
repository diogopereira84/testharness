/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/modal/modal',
    'hierarchyTreePopup',
    'mage/translate'
], function ($, registry, _) {
    'use strict';
    var userPermissionRoles = typeof (window.checkout.user_roles_permission) != 'undefined' && window.checkout.user_roles_permission != null ? window.checkout.user_roles_permission : false;
    var companySettingsToggle = typeof (window.checkout.explorers_company_settings_toggle) != 'undefined' && window.checkout.explorers_company_settings_toggle != null ? window.checkout.explorers_company_settings_toggle : false;
    var userGroupAndFolderLevelPermissionToggle = typeof (window.checkout.sgcUserGroupAndFolderLevelPermission) != 'undefined' && window.checkout.sgcUserGroupAndFolderLevelPermission != null ? window.checkout.sgcUserGroupAndFolderLevelPermission : false;
    

    $.widget('mage.userEdit', {

        options: {
            popup: '[data-role="add-customer-dialog"]',
            statusSelect: '[data-role="status-select"]',
            roleSelect: '[data-role="role-select"]',
            isAjax: false,
            gridProvider: '',
            adminUserRoleId: 0,
            getUserUrl: '',
            additionalFields: {
                create: '[data-role="create-additional-fields"]',
                edit: '[data-role="edit-additional-fields"]'
            }
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this._setModal();
            this._bind();
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            this._on({
                'editUser': 'editUser',
                'click': 'editUser',
                'reloadTheTree': '_reloadGrid'
            });
        },

        /**
         * Callback for edit event
         *
         * @param {Object} e
         * @public
         */
        editUser: function (e) {
            var title = this.options.id ? $.mage.__('Edit User Group') : $.mage.__('Add New User');

            if (e) {
                e.preventDefault();
            }
            this.options.popup.modal('setTitle', title);
            this.options.popup.modal('openModal');

            $(".company-users-index .modal-inner-wrap .action-close").attr("data-test-id", "E-404291-B-2006241-TK-3416887-manage-user-close","aria-label", "Close");
            $('.company-users-index .modal-inner-wrap h1.modal-title').replaceWith(function() {
              return $("<h2>", {
                class: this.className,
                id: this.id,
                html: $(this).html()
              });
            });
            
            if (userPermissionRoles) {
                $('.company-users-index #add-user-form').trigger("reset");
                $(".company-users-index #add-user-form .email-approval-section").hide();
            }

            this._populateForm();
            this._setIdFields();

            if (!this.options.id) {
                if(userGroupAndFolderLevelPermissionToggle) {
                    this._filterRoles('role');
                }
            }
        },

        /**
         * Toggle show addition fields
         *
         * @param {Boolean} isRegisterForm
         * @private
         */
        showAdditionalFields: function (isRegisterForm) {
            $(this.options.additionalFields.create).toggleClass('_hidden', isRegisterForm)
                .find('[name]').prop('disabled', isRegisterForm);
            $(this.options.additionalFields.edit).toggleClass('_hidden', !isRegisterForm)
                .find('[name]').prop('disabled', !isRegisterForm);
        },

        /**
         * Callback for reload event
         *
         * @private
         */
        _reloadGrid: function () {
            this._getGridProvider().reload({
                refresh: true
            });
        },

        /**
         * Get provider
         *
         * @private
         */
        _getGridProvider: function () {
            if (!this.gridProvider) {
                this.gridProvider = registry.get(this.options.gridProvider);
            }

            return this.gridProvider;
        },

        /**
         * Set id customer to field in form
         *
         * @private
         */
        _setIdFields: function () {
            this.options.popup.find('[name="customer_id"]').val(this.options.id);
        },

        /**
         * Set modal for edit customer
         *
         * @private
         */
        _setModal: function () {
            var self = this;

            this.options.popup = $(this.options.popup).hierarchyTreePopup({
                popupTitle: self.options.popupTitle,
                treeSelector: self.element,
                buttons: [{
                    text: $.mage.__('Save'),
                    'class': 'action save primary',

                    /** @inheritdoc */
                    click: function () {
                       
                        self.options.popup.trigger('sendForm');
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    'class': 'action cancel secondary',

                    /** @inheritdoc */
                    click: function () {
                        this.closeModal();
                    }
                }]
            });
        },

        /**
         * Set data to popup form fields
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _setPopupFields: function (name, value, editedUserGroupIdValue = 0, isCustomerGroupEdited = false) {
            var self = this;

            if (!userGroupAndFolderLevelPermissionToggle) {
                if (name === 'group_id') {
                    let userGroupName;
                    if (!isCustomerGroupEdited){
                        userGroupName = self.options.userGroupName;
                    }
                    
                    if (userGroupName) {
                        $('#usergroup').val(userGroupName);
                    } else if (value) {
                        $('#usergroup').val(value);
                        $('#editedUserGroupId').val(editedUserGroupIdValue);
                    } else {
                        $('#usergroup').val('Default');
                    }
                }
            }

            if(userGroupAndFolderLevelPermissionToggle) {
                if (name === 'role') {
                    self._filterRoles(name, value);
                }
            }
            
            if (name === 'extension_attributes[company_attributes][status]') {
                $('.form-add-user').find("#status-value-active").val("1");
                $('.form-add-user').find("#status-value-inactive").val("0");
                if(value == "1") {
                    $('.form-add-user').find("#status-value-active").prop("checked", true);
                } else if(value == "0") {
                    $('.form-add-user').find("#status-value-inactive").prop("checked", true);
                } else if(value == "2") {
                    $('.form-add-user').find("#status-value-inactive").prop("checked", false);
                    $('.form-add-user').find("#status-value-active").prop("checked", false);
                }
            }
            if(name == "role_permissions") {
                $('.edit_single_role_permissions').each(function(){
                    let rolePermision = $(this).val();
                    if (Object.values(value).indexOf(rolePermision) > -1) {
                        //$(this).attr("checked","checked");
                        $(this).trigger("click");
                    }
                });
            }
            this.options.popup.find('form [name="' + name + '"]').val(value);
        },

        /**
         * Set multi line values
         *
         * @param {String} name
         * @param {String} id
         * @param {String} value
         */
        setMultilineValues: function (name, id, value) {
            var self = this;

            if(userGroupAndFolderLevelPermissionToggle) {
                if (name === 'role') {
                    self._filterRoles(name, value);
                }
            }
            this.options.popup.find('form [id="' + id + '"]').val(value);
        },

        /**
         * Set multi select options
         *
         * @param {String} name
         * @param {String} value
         */
        setMultiSelectOptions: function (name, value) {
            var self = this,
                selectValues =  value.split(',');

            if(userGroupAndFolderLevelPermissionToggle) {
                if (name === 'role') {
                    self._filterRoles(name, value);
                }
            }

            this.options.popup.find('form [name="' + name + '"]').val(selectValues);
        },

        /**
         * Fill roles input field.
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _filterRoles: function (name, value) {
            var selectRoles = this.options.popup.find(this.options.roleSelect),
                statusSelect = this.options.popup.find(this.options.statusSelect),
                optionsRole = selectRoles.find('option'),
                adminRole = selectRoles.find('[value=' + this.options.adminUserRoleId + ']'),
                condition = value === this.options.adminUserRoleId;

            selectRoles.prop('disabled', condition);
            statusSelect.prop('disabled', condition);
            optionsRole.toggle(!condition);
            adminRole.toggle(condition);
            adminRole.attr('disabled', condition ? 'disabled' : '');

            if (_.isUndefined(value)) {
                optionsRole.first().attr('selected', 'selected');
            }
        },

        /**
         * Populate form
         *
         * @private
         */
        _populateForm: function () {
            var self = this;

            this.showAdditionalFields(!this.options.id);
            let inputEle = this.options.popup.find('input');
            inputEle.each(function(ele){
                if(!$(this).hasClass("skip_pre_populate")) {
                    $(this).val('');
                }
            });
            
            this.options.popup.find('select').val('');
            this.options.popup.find('textarea').val('');

            if (!this.options.isAjax && this.options.id) {
                this.options.isAjax = true;

                this.options.popup.addClass('unpopulated');
                this.options.popup.find('input').attr('disabled', true);

                $.ajax({
                    url: self.options.getUserUrl,
                    type: 'get',
                    showLoader: true,

                    /**
                     * @callback
                     */
                    success: $.proxy(function (data) {
                        var that = this;

                        this.options.popup.find('input').attr('disabled', false);

                        if (data.status === 'ok') {
                            $.each(data.data, function (idx, item) {
                                if (idx === 'custom_attributes') {
                                    $.each(item, function (name, itemData) {
                                        var customAttributeCode = itemData['attribute_code'],
                                            issetPopupField = false,
                                            multilineAttributeCode,
                                            multilineAttributeValue,
                                            multilineAttributeId,
                                            multiSelectAttributeCode,
                                            key;

                                        if (itemData.hasOwnProperty('attributeType')) {
                                            customAttributeCode = 'customer_account_create-'.
                                            concat(customAttributeCode);
                                        }

                                        if (itemData.hasOwnProperty('attributeType') && itemData.value) {

                                            if (itemData.attributeType === 'multiline') {

                                                multilineAttributeCode = customAttributeCode + '[]';
                                                multilineAttributeValue = itemData.value.split('\n');

                                                // eslint-disable-next-line max-depth
                                                for (key = 0; key < multilineAttributeValue.length; key++) {
                                                    multilineAttributeId = customAttributeCode + '_' + key;

                                                    that.setMultilineValues(
                                                        multilineAttributeCode,
                                                        multilineAttributeId,
                                                        multilineAttributeValue[key]
                                                    );

                                                    issetPopupField = true;
                                                }
                                            } else if (itemData.attributeType === 'multiselect') {

                                                multiSelectAttributeCode = customAttributeCode + '[]';

                                                that.setMultiSelectOptions(multiSelectAttributeCode, itemData.value);

                                                issetPopupField = true;
                                            }
                                        }

                                        if (!issetPopupField) {
                                            that._setPopupFields(customAttributeCode, itemData.value);
                                        }
                                    });
                                }
                                that._setPopupFields(idx, item);
                            });
                            this.options.popup.removeClass('unpopulated');
                        }

                        if (userPermissionRoles && data && data !== undefined && data.data && data.data !== undefined && data.data.role !== undefined ) {
                            if(userGroupAndFolderLevelPermissionToggle) {
                                $('.form-add-user').find("div.custom_role.disabled-field .role-button")[0].innerText="Default User";
                            }
                            if (!companySettingsToggle) {
                                $('.form-add-user').find('.shared_credit_cards-tooltip-message').text("Users with this access will be able to add, edit or delete credit card information for this site.");
                                $('.form-add-user').find('.site_settings').hide();
                                $('div.shared_credit_cards').find('label').text('Shared Credit Cards');
                            }                 
                            if (data.data.role == 0) {
                                $(".form-add-user #manage_users").trigger('click').prop('disabled', true);
                                $(".form-add-user #shared_orders").trigger('click').prop('disabled', true);
                                $(".form-add-user #shared_credit_cards").trigger('click').prop('disabled', true);
                                $(".form-add-user #manage_catalog").trigger('click').prop('disabled', true);
                                $(".form-add-user #email-approval-yes").trigger('click').prop('disabled', true);
                                $(".form-add-user #email-approval-no").prop('disabled', true);
                                $('.form-add-user').find("#manage_users").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#shared_orders").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#shared_credit_cards").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#manage_catalog").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#review_orders").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#site_settings").prop({checked:true,disabled:true});
                                $('.form-add-user').find("#email-approval-yes").trigger('click').prop('disabled', true);
                                $('.form-add-user').find("#email-approval-no").prop('disabled', true);
                                $('.form-add-user').find("#status-value-active").trigger('click').prop('disabled', true);
                                $('.form-add-user').find("#status-value-inactive").prop('disabled', true);
                                $('.form-add-user').find(".email-approval-section").show();
                                if(userGroupAndFolderLevelPermissionToggle) {
                                    $('.form-add-user').find("div.custom_role.disabled-field")[0].innerText="Admin"; 
                                    $('.form-add-user').find("div.custom_role.disabled-field").css({"line-height":"24px"}); 
                                    $('.form-add-user').find("div.custom_role.disabled-field")[0].innerText="Admin";
                                }                      
                            }
                        }
                    }, this),

                    /**
                     * @callback
                     */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        }
    });

    return $.mage.userEdit;
});
