<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\User\Model\UserFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\CountryFactory;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\NegotiableQuote\Api\CompanyQuoteConfigManagementInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ExportCompanyData extends AbstractHelper
{
    public const EPRO_LOGIN_METHOD = 'commercial_store_epro';
    public const WLGN_LOGIN_METHOD = 'commercial_store_wlgn';
    public const SSO_LOGIN_METHOD = 'commercial_store_sso';
    public const SSO_WITH_FCL_LOGIN_METHOD = 'commercial_store_sso_with_fcl';
    public const MASK_DIGIT = '000000';

    /**
     * Export Company Data Constructor
     *
     * @param Context $context
     * @param UrlInterface $urlInterface
     * @param UserFactory $userFactory
     * @param RegionFactory $regionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CountryFactory $countryFactory
     * @param AuthDynamicRowsFactory $ruleFactory
     * @param CreditDataProviderInterface $creditDataProviderInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param CompanyQuoteConfigManagementInterface $companyQuoteConfigManagementInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        protected UrlInterface $urlInterface,
        protected UserFactory $userFactory,
        protected RegionFactory $regionFactory,
        protected CustomerRepositoryInterface $customerRepository,
        protected CountryFactory $countryFactory,
        protected AuthDynamicRowsFactory $ruleFactory,
        protected CreditDataProviderInterface $creditDataProviderInterface,
        protected GroupRepositoryInterface $groupRepositoryInterface,
        protected CompanyQuoteConfigManagementInterface $companyQuoteConfigManagementInterface,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Export Company General Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportCompanyGeneralTab($company)
    {
        $data = [
            'company_name' => !empty($company->getCompanyName()) ? $company->getCompanyName() : '',
            'status' => $this->getCompanyStatus($company->getStatus()),
            'company_email' => !empty($company->getCompanyEmail()) ? $company->getCompanyEmail() : '',
            'sales_representative' => $this->getSalesRepresentativeEmail($company->getSalesRepresentativeId()),
            'url_extention' => !empty($company->getCompanyUrlExtention())
             ? $company->getCompanyUrlExtention() : '',
            'sensitive_data_enabled' => !empty($company->getIsSensitiveDataEnabled())
             ? $company->getIsSensitiveDataEnabled() : "0"
        ];

        return json_encode($data);
    }

    /**
     * Get Company Status
     *
     * @param int $companyStatus
     * @return string|null
     */
    public function getCompanyStatus($companyStatus)
    {
        $status = null;
        if ($companyStatus == 1) {
            $status = 'Active';
        } elseif ($companyStatus == 2) {
            $status = 'Rejected';
        } elseif ($companyStatus == 3) {
            $status = 'Blocked';
        } else {
            $status = 'Pending Approval';
        }

        return $status;
    }

    /**
     * Get Sales Representative Email
     *
     * @param int $salesRepresentativeId
     * @return string|null
     */
    public function getSalesRepresentativeEmail($salesRepresentativeId)
    {
        $user = $this->userFactory->create()->load($salesRepresentativeId);

        return $user->getUsername() ?? '';
    }

    /**
     * Export UI Customization
     *
     * @param object $company
     * @return json
     */
    public function exportUiCustomization($company)
    {
        $companyLogo = $company->getCompanyLogo();
        $companyLogoArray = json_decode($companyLogo, true);
        $baseUrl = rtrim($this->urlInterface->getBaseUrl(), '/');
        $logoUrl = '';
        if (!empty($companyLogoArray['url'])) {
            $logoUrl = $baseUrl.$companyLogoArray['url'];
        }

        $data = [
            'logo_image' => $logoUrl
        ];

        return json_encode($data);
    }

    /**
     * Export Cxml Notification Message Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportCxmlNotificationTab($company)
    {
        $data = [
            'order_complete_confirm' => !empty($company->getOrderCompleteConfirm())
             ? $company->getOrderCompleteConfirm() : "0",
            'shipping_notification_or_delivery_options' => !empty($company->getShipnotfDelivery())
             ? $company->getShipnotfDelivery() : "0",
            'order_cancel_customer' => !empty($company->getOrderCancelCustomer())
             ? $company->getOrderCancelCustomer() : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export Catalog and Document User Settings Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportCatalogAndDocumentTab($company)
    {
        $getAdditionalDataObject = $this->getCompanyAdditionalDataObj($company);
        $isReorderEnabled = $getAdditionalDataObject->getIsReorderEnabled();
        $data = [
            'reorder' => $isReorderEnabled ?? "0",
            'allow_own_document' => !empty($company->getAllowOwnDocument())
             ? $company->getAllowOwnDocument() : "0",
            'allow_shared_catalog' => !empty($company->getAllowSharedCatalog())
             ? $company->getAllowSharedCatalog() : "0",
            'box_cloud_drive_integration_option' => !empty($company->getBoxEnabled())
             ? $company->getBoxEnabled() : "0",
            'dropbox_cloud_drive_integration_option' => !empty($company->getDropboxEnabled())
             ? $company->getDropboxEnabled() : "0",
            'google_cloud_drive_integration_option' => !empty($company->getGoogleEnabled())
             ? $company->getGoogleEnabled() : "0",
            'microsoft_cloud_drive_integration_option' => !empty($company->getMicrosoftEnabled())
             ? $company->getMicrosoftEnabled() : "0",
        ];

        return json_encode($data);
    }

    /**
     * Get company additional data object
     *
     * @param object $company
     * @return object
     */
    public function getCompanyAdditionalDataObj($company)
    {
        return $company->getExtensionAttributes()->getCompanyAdditionalData();
    }

    /**
     * Export FXO Web Analytics Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportFxoWebAnalyticsTab($company)
    {
        $data = [
            'content_square' => !empty($company->getContentSquare()) ? $company->getContentSquare() : "0",
            'adobe_analytics' => !empty($company->getAdobeAnalytics()) ? $company->getAdobeAnalytics() : "0",
            'app_dynamics' => !empty($company->getAppDynamics()) ? $company->getAppDynamics() : "0",
            'forsta' => !empty($company->getForsta()) ? $company->getForsta() : "0",
            'nuance' => !empty($company->getNuance()) ? $company->getNuance() : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export Email Notification Options Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportEmailNotificationOptionsTab($company)
    {
        $data = [
            'is_quote_request' => !empty($company->getIsQuoteRequest()) ? $company->getIsQuoteRequest() : "0",
            'is_expiring_order_email' => !empty($company->getIsExpiringOrder())
             ? $company->getIsExpiringOrder() : "0",
            'is_expired_order_email' => !empty($company->getIsExpiredOrder()) ? $company->getIsExpiredOrder() : "0",
            'is_order_reject_email' => !empty($company->getIsOrderReject()) ? $company->getIsOrderReject() : "0",
            'is_success_email_enable' => !empty($company->getIsSuccessEmailEnable())
             ? $company->getIsSuccessEmailEnable() : "0",
            'bcc_comma_seperated_email' => !empty($company->getBccCommaSeperatedEmail())
             ? $company->getBccCommaSeperatedEmail() : '',
            'order_confirmation_email_template' => !empty($company->getOrderConfirmationEmailTemplate())
            ? $company->getOrderConfirmationEmailTemplate() : '0',
        ];

        return json_encode($data);
    }

    /**
     * Export Upload To Quote Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportUploadToQuoteTab($company)
    {
        $data = [
            'allow_upload_to_quote' => !empty($company->getAllowUploadToQuote())
             ? $company->getAllowUploadToQuote() : "0",
            'enable_next_step_content_to_display' => !empty($company->getAllowNextStepContent())
             ? $company->getAllowNextStepContent() : "0",
            'upload_to_quote_next_step_content' => !empty($company->getUploadToQuoteNextStepContent())
             ? strip_tags($company->getUploadToQuoteNextStepContent()) : null
        ];

        return json_encode($data);
    }

    /**
     * Export Order Settings Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportOrderSettingsTab($company)
    {
        $additionalObject = $this->getCompanyAdditionalDataObj($company);
        $data = [
            'order_notes' => !empty($additionalObject->getOrderNotes())
             ? $additionalObject->getOrderNotes() : null,
            'terms_and_conditions' => !empty($additionalObject->getTermsAndConditions())
             ? $additionalObject->getTermsAndConditions() : "0",
            'is_promo_discount_enabled' => !empty($additionalObject->getIsPromoDiscountEnabled())
             ? $additionalObject->getIsPromoDiscountEnabled() : "0",
            'is_account_discount_enabled' => !empty($additionalObject->getIsAccountDiscountEnabled())
            ? $additionalObject->getIsAccountDiscountEnabled() : "0",
            'epro_new_platform_order_creation' => !empty($additionalObject->getEproNewPlatformOrderCreation())
            ? $additionalObject->getEproNewPlatformOrderCreation() : "0",
        ];

        return json_encode($data);
    }

    /**
     * Export Authentication Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportAuthenticationTab($company)
    {
        $data = [];
        if ($company->getStorefrontLoginMethodOption() === static::EPRO_LOGIN_METHOD) {
            $rulesData = $this->ruleFactory->create()
                    ->getCollection()->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $company->getId()]);
            $rules = [];
            $count = 0;
            foreach ($rulesData as $rule) {
                $rules[$count]['rule_type'] = $rule->getType();
                $rules[$count]['rule_code'] = $rule->getRuleCode();
                $count++;
            }
            $data = [
                'storefront_login_method_option' => $company->getStorefrontLoginMethodOption(),
                'domain_name'                    => $company->getDomainName(),
                'domain_id'                      => $company->getNetworkId(),
                'site_name'                      => $company->getSiteName(),
                'acceptance_option'              => $company->getAcceptanceOption(),
                'rule_data'                      => $rules
            ];
        } elseif($company->getStorefrontLoginMethodOption() === static::WLGN_LOGIN_METHOD) {
            $selfRegData = json_decode($company->getData('self_reg_data'), true);
            $data = [
                'storefront_login_method_option' => $company->getStorefrontLoginMethodOption(),
                'self_reg_data'                  => $selfRegData
            ];
        } elseif ($company->getStorefrontLoginMethodOption() === static::SSO_LOGIN_METHOD ||
            $company->getStorefrontLoginMethodOption() === static::SSO_WITH_FCL_LOGIN_METHOD) {
            $data = [
                'storefront_login_method_option' => $company->getStorefrontLoginMethodOption(),
                'sso_login_url'  => $company->getSsoLoginUrl(),
                'sso_logout_url' => $company->getSsoLogoutUrl(),
                'sso_idp'        => $company->getSsoIdp()
            ];
            if ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')) {
                $data = [
                    'sso_group' => $company->getSsoGroup(),
                ];
            }
        }

        return json_encode($data);
    }

    /**
     * Export Payment Methods Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportPaymentMethodsTab($company)
    {
        $additionalObject = $this->getCompanyAdditionalDataObj($company);
        $fedexShippingRefInfo = $company->getCustomBillingShipping();
        $customBillingInvoiceInfo = $company->getCustomBillingInvoiced();
        $customBillingCcInfo = $company->getCustomBillingCreditCard();
        $billingShipInfoDecodedInfo = json_decode($fedexShippingRefInfo, true);
        $customInvoiceInfoDecodedInfo = json_decode($customBillingInvoiceInfo, true);
        $customCcInfoDecodedInfo = json_decode($customBillingCcInfo, true);
        if (!empty($billingShipInfoDecodedInfo[0]) && empty($billingShipInfoDecodedInfo[0]['mask'])) {
            $billingShipInfoDecodedInfo[0]['mask'] = 'No';
        }
        foreach ($customInvoiceInfoDecodedInfo as $key => $customInvoiceInfo) {
            $customInvoiceInfoDecodedInfo[$key]["mask"] =
            empty($customInvoiceInfoDecodedInfo[$key]["mask"]) ? 'No' : $customInvoiceInfoDecodedInfo[$key]["mask"];
        }
        foreach ($customCcInfoDecodedInfo as $key => $customCcInfo) {
            $customCcInfoDecodedInfo[$key]["mask"] =
            empty($customCcInfoDecodedInfo[$key]["mask"]) ? 'No' : $customCcInfoDecodedInfo[$key]["mask"];
        }

        $data = [
            'applicable_payment_method' => !empty($additionalObject->getCompanyPaymentOptions())
             ? $additionalObject->getCompanyPaymentOptions() : [],
            'default_payment_method' => !empty($additionalObject->getDefaultPaymentMethod())
             ? $additionalObject->getDefaultPaymentMethod() : null,
            'fedex_account_options' => !empty($additionalObject->getFedexAccountOptions())
             ? $additionalObject->getFedexAccountOptions() : null,
            'fxo_account_number' => !empty($company->getFedexAccountNumber())
             ? self::MASK_DIGIT : null,
            'fxo_account_number_editable' => !empty($company->getFxoAccountNumberEditable())
             ? $company->getFxoAccountNumberEditable() : "0",
            'shipping_account_number' => !empty($company->getShippingAccountNumber())
             ? self::MASK_DIGIT : null,
            'shipping_account_number_editable' => !empty($company->getShippingAccountNumberEditable())
             ? $company->getShippingAccountNumberEditable() : "0",
            'discount_account_number' => !empty($company->getDiscountAccountNumber())
             ? self::MASK_DIGIT : null,
            'discount_account_number_editable' => !empty($company->getDiscountAccountNumberEditable())
             ? $company->getDiscountAccountNumberEditable() : "0",
            'credit_card_options' => !empty($additionalObject->getCreditcardOptions())
            ? $additionalObject->getCreditcardOptions() : null,
            'fedex_shipping_reference_field' => $billingShipInfoDecodedInfo,
            'custom_billing_fields_invoiced_account' => $customInvoiceInfoDecodedInfo,
            'custom_billing_fields_credit_card' => $customCcInfoDecodedInfo,
        ];

        return json_encode($data);
    }

    /**
     * Export Notification Banner Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportNotificationBannerTab($company)
    {
        $additionalObject = $this->getCompanyAdditionalDataObj($company);
        $data = [
            'is_banner_enable' => !empty($additionalObject->getIsBannerEnable())
             ? $additionalObject->getIsBannerEnable() : "0",
            'banner_title' => !empty($additionalObject->getBannerTitle())
             ? $additionalObject->getBannerTitle() : null,
            'description' => !empty($additionalObject->getDescription())
             ? strip_tags($additionalObject->getDescription()) : null,
            'iconography' => !empty($additionalObject->getIconography())
             ? ucwords($additionalObject->getIconography()) : null,
            'cta_text' => !empty($additionalObject->getCtaText())
             ? $additionalObject->getCtaText() : null,
            'cta_link' => !empty($additionalObject->getCtaLink())
             ? $additionalObject->getCtaLink() : null,
            'link_open_in_new_tab' => !empty($additionalObject->getLinkOpenInNewTab())
             ? $additionalObject->getLinkOpenInNewTab() : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export Delivery Options Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportDeliveryOptionsTab($company)
    {
        $data = [
            'shipment' => !empty($company->getIsDelivery())
             ? $company->getIsDelivery() : "0",
            'allowed_shipping_options' => !empty($company->getAllowedDeliveryOptions())
             ? $company->getAllowedDeliveryOptions() : [],
            'store_pickup' => !empty($company->getIsPickup()) ? $company->getIsPickup() : "0",
            'hc_toggle' => !empty($company->getHcToggle()) ? $company->getHcToggle() : "0",
            'recipient_address_from_po' => !empty($company->getRecipientAddressFromPo())
             ? $company->getRecipientAddressFromPo() : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export Account Information Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportAccountInformationTab($company)
    {
        $data = [
            'legal_name' => !empty($company->getLegalName())
             ? $company->getLegalName() : null,
            'vat_tax_id' => !empty($company->getVatTaxId())
             ? $company->getVatTaxId() : null,
            'reseller_id' => !empty($company->getResellerId())
             ? $company->getResellerId() : null,
            'comment' => !empty($company->getComment())
             ? $company->getComment() : null
        ];

        return json_encode($data);
    }

    /**
     * Export Legal Address Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportLegalAddressTab($company)
    {
        $regionName = '';
        if (!empty($company->getRegionId())) {
            $regionObject = $this->regionFactory->create()->load($company->getRegionId());
            $regionName = $regionObject->getName();
        }

        $countryName = null;
        if (!empty($company->getCountryId())) {
            $country = $this->countryFactory->create()->loadByCode($company->getCountryId());
            $countryName = $country->getName();
        }

        $data = [
            'street' => !empty($company->getStreet())
             ? $company->getStreet() : null,
            'city' => !empty($company->getCity())
             ? $company->getCity() : null,
            'country_id' => !empty($company->getCountryId())
             ? $countryName : null,
            'state_or_province' => $regionName,
            'postcode' => !empty($company->getPostcode())
             ? $company->getPostcode() : null,
            'telephone' => !empty($company->getTelephone())
             ? $company->getTelephone() : null
        ];

        return json_encode($data);
    }

    /**
     * Get Customer Status
     *
     * @param int $customerStatus
     * @return string|null
     */
    public function getCustomerStatus($customerStatus)
    {
        $status = null;
        if ($customerStatus == 0) {
            $status = 'Inactive';
        } elseif ($customerStatus == 1) {
            $status = 'Active';
        } elseif ($customerStatus == 2) {
            $status = 'Pending For Approval';
        } else {
            $status = 'Email Verification Pending';
        }
        return $status;
    }

    /**
     *Get Website Status
     *
     * @param int $websitestatus
     * @return string|null
     */
    public function getWebsiteStatus($websitestatus)
    {
        $status = null;
        if ($websitestatus == 1) {
            $status = 'Main Website';
        }
        return $status;
    }

    /**
     * Export Company Admin Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportCompanyAdminTab($company)
    {
        $customer = $this->customerRepository->getById($company->getSuperUserId());
        $data = [];
        if ($customer !== null) {
            $data = [
                'contact_number' => !empty($customer->getCustomAttribute('contact_number')) ?
                $customer->getCustomAttribute('contact_number')->getValue() : null,
                'ext' => !empty($customer->getCustomAttribute('contact_ext')) ?
                $customer->getCustomAttribute('contact_ext')->getValue() : null,
                'customer_status' => $this->getCustomerStatus
                ($customer->getCustomAttribute('customer_status')->getValue()),
                'website' => $this->getWebsiteStatus($customer->getWebsiteId()),
                'email' => $customer->getEmail(),
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'fcl_profile_contact_number' => !empty($customer->getCustomAttribute('fcl_profile_contact_number')) ?
                $customer->getCustomAttribute('fcl_profile_contact_number')->getValue() : null,
                'secondary_email' => !empty($customer->getCustomAttribute('secondary_email')) ?
                $customer->getCustomAttribute('secondary_email')->getValue() : null,
            ];
        }

        return json_encode($data);
    }

    /**
     * Export Company Credit Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportCompanyCreditTab($company)
    {
        $creditCardDataObject = $this->creditDataProviderInterface->get($company->getId());
        $data = [
            'credit_currency' => !empty($creditCardDataObject->getCurrencyCode()) ? 'US Dollar' : '',
            'credit_limit' => !empty($creditCardDataObject->getCreditLimit())
             ? $creditCardDataObject->getCreditLimit() : null,
            'allow_to_exceed_credit_limit' => !empty($creditCardDataObject->getExceedLimit())
             ? "1" : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export Advanced Settings Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportAdvancedSettingsTab($company)
    {
        $customerGroupCode = null;
        if (!empty($company->getCustomerGroupId())) {
            $customerGroupObject = $this->groupRepositoryInterface->getById($company->getCustomerGroupId());
            $customerGroupCode = $customerGroupObject->getCode();
        }
        $companyQuoteConfigObject = $this->companyQuoteConfigManagementInterface->getByCompanyId($company->getId());
        $isPurchaseOrderEnabled = $company->getExtensionAttributes()->getIsPurchaseOrderEnabled();
        $data = [
            'customer_group' => $customerGroupCode,
            'allow_quotes' => !empty($companyQuoteConfigObject->getIsQuoteEnabled()) ? "1" : "0",
            'enable_purchase_orders' => !empty($isPurchaseOrderEnabled) ? "1" : "0"
        ];

        return json_encode($data);
    }

    /**
     * Export MVP Catalog Setting Tab Data
     *
     * @param object $company
     * @return json
     */
    public function exportMvpCatalogSettingTab($company)
    {
        $data = [
            'is_catalog_mvp_enabled' => !empty($company->getIsCatalogMvpEnabled())
             ? $company->getIsCatalogMvpEnabled() : "0"
        ];

        return json_encode($data);
    }
}
