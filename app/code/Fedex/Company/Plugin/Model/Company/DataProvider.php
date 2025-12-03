<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Plugin\Model\Company;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\Session;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Model\Company\Custom\Billing\Invoiced\Mapper as InvoicedMapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper as CreditCardMapper;
use Fedex\Company\Model\Company\Custom\Billing\Shipping\Mapper as ShippingMapper;

/**
 * Plugin class for model DataProvider
 */
class DataProvider
{
    /**
      * Initializing constructor
      *
      * @param ToggleConfig $toggleConfig
      * @param AdminConfigHelper $orderApprovalB2BHelper
      * @param ConfigInterface $configInterface
      * @param Session $adminSession
      * @param RequestInterface $request
      * @param InvoicedMapper $invoicedMapper
      * @param CreditCardMapper $creditCardMapper
      * @param ShippingMapper $shippingMapper
      */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private AdminConfigHelper $orderApprovalB2BHelper,
        private ConfigInterface $configInterface,
        private Session $adminSession,
        private RequestInterface $request,
        private InvoicedMapper $invoicedMapper,
        private CreditCardMapper $creditCardMapper,
        private ShippingMapper $shippingMapper,
        private readonly OndemandConfigInterface $ondemandConfigInterface
    )
    {
    }

    /**
     * Append company meta data to result
     *
     * @param object $subject
     * @param array $result
     * @return array
     */
    public function afterGetMeta($subject, $result)
    {
        if ($subject->getName() == 'company_form_data_source') {
            if (!$this->orderApprovalB2BHelper->isOrderApprovalB2bGloballyEnabled()) {
                $result['production_location']['children']['is_b2b_order_approval_enabled']
                ['arguments']['data']['config']['visible'] = false;
            }

            if (!$this->ondemandConfigInterface->isTigerD239305ToggleEnabled()) {
                $result['catalog_document']['children']['office_supplies_enabled']
                ['arguments']['data']['config']['visible'] = false;
                $result['catalog_document']['children']['shipping_packing_mailing_enabled']
                ['arguments']['data']['config']['visible'] = false;
            }

            if (!$this->configInterface->getE414712HeroBannerCarouselForCommercial()) {
                $result['company_logo']['children']['homepage_cms_block_identifier']['arguments']['data']['config']
                ['visible'] = false;
            }

            $isChangeAdminButtonToggle = $this->toggleConfig->getToggleConfigValue('sgc_change_admin_button');

            if ($isChangeAdminButtonToggle) {
                $companyId = $this->request->getParam('id');

                $isDisabled = true;
                if ($companyId) {
                    $isDisabled = false;
                    $this->adminSession->setCompanyAdminId($companyId);
                }

                $config = [
                    'company_admin' => [
                        'children' => [
                            'change_admin_button' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'disabled' => $isDisabled
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $result = array_merge_recursive($result, $config);
            }

            $result['settings']['children']['payment_container']['arguments']['data']['config']['visible'] = false;
            $result['settings']['children']['payment_container']['children']
            ['extension_attributes.applicable_payment_method']['arguments']['data']['config']['visible'] = false;
            $result['settings']['children']['payment_container']['children']
            ['extension_attributes.use_config_settings']['arguments']['data']['config']['visible'] = false;

            $result['settings']['children']['shipping_container']['arguments']['data']['config']['visible'] = false;
            $result['settings']['children']['shipping_container']['children']
            ['extension_attributes.applicable_shipping_method']['arguments']['data']['config']['visible'] = false;
            $result['settings']['children']['shipping_container']['children']
            ['extension_attributes.use_config_settings_shipping']['arguments']['data']['config']['visible'] = false;

            $result['settings']['children']['extension_attributes.available_payment_methods']['arguments']['data']
            ['config']['visible'] = false;
            $result['settings']['children']['extension_attributes.available_shipping_methods']['arguments']['data']
            ['config']['visible'] = false;
        } elseif ($subject->getName() == 'selfreg_company_form_data_source') {
            $result['self_reg_login']['children']['enable_selfreg']['arguments']
            ['data']['config']['visible'] = false;
            $result['self_reg_login']['arguments']['data']['config']['visible'] = false;
        } elseif ($subject->getName() == 'mvp_catalog_company_form_data_source') {
            if (key_exists('company_admin', $result)) {
                unset($result['company_admin']);
            }
        }
            $isRemoveCompanyAdminToggle = $this->toggleConfig->getToggleConfigValue('sgc_remove_companyadmin_fields');
        if ($isRemoveCompanyAdminToggle) {
            unset($result["company_admin"]["children"]["external_identifier"]);
            unset($result["company_admin"]["children"]["contact_number"]);
            unset($result["company_admin"]["children"]["contact_ext"]);
            unset($result["company_admin"]["children"]["unique_id"]);
            unset($result["company_admin"]["children"]["customer_status"]);
            unset($result["company_admin"]["children"]["fcl_profile_contact_number"]);
            unset($result["company_admin"]["children"]["customer_uuid_value"]);
            unset($result["company_admin"]["children"]["customer_canva_id"]);
            unset($result["company_admin"]["children"]["seller_configurator_uuid"]);
        }

        return $result;
    }

    /**
     * Append company custom general data to result
     *
     * @param object $subject
     * @param array $result
     * @param CompanyInterface $company
     * @return array
     */
    public function afterGetGeneralData($subject, $result, CompanyInterface $company)
    {
            $result['company_url'] = $company->getCompanyUrl();
            $result['company_url_extention'] = $company->getCompanyUrlExtention() ?? '';
            $result['is_sensitive_data_enabled'] = $company->getIsSensitiveDataEnabled() ?? '';

        return $result;
    }

    /**
     * Append company custom result data to result
     *
     * @param object $subject
     * @param array $result
     * @param CompanyInterface $company
     * @return array
     */
    public function afterGetCompanyResultData($subject, $result, CompanyInterface $company)
    {
            $result = [
                $subject::DATA_SCOPE_GENERAL => array_merge(
                    $subject->getGeneralData($company),
                    $subject->getStoreDetails($company),
                    $subject->getNewStoreDetails($company),
                ),
                $subject::DATA_SCOPE_INFORMATION => $subject->getInformationData($company),
                $subject::DATA_SCOPE_ADDRESS => $subject->getAddressData($company),
                $subject::DATA_SCOPE_COMPANY_ADMIN => $subject->getCompanyAdminData($company),
                $subject::DATA_SCOPE_SETTINGS => $subject->getSettingsData($company),
                $subject::DATA_SCOPE_AUTHENTICATION => $subject->getAuthenticationData($company),
                $subject::DATA_SCOPE_SHIPPINGOPTIONS => $subject->getShippingOptionsData($company),
                $subject::DATA_SCOPE_EMAILNOTIFICATION => $subject->getEmailNotificationData($company),
                $subject::DATA_SCOPE_CATALOGANDDOCUMENT => $subject->getCatalogAndDocumentsData($company),
                $subject::DATA_SCOPE_UPLOADTOQUOTE => $subject->getUploadToQuoteData($company),
                $subject::SHARED_CATALOG_ID => $subject->getSharedCatalogId($company),
                $subject::DATA_SCOPE_PAYMENTMETHODS => $subject->getPaymentMethodsData($company),
                $subject::DATA_SCOPE_CXMLNOTIFICATION => $subject->getCxmlNotificationData($company),
                $subject::DATA_SCOPE_HOMEPAGESETTINGS => $subject->getHomepageSettingsData($company),
                $subject::DATA_SCOPE_ADDITIONALDATA => $subject->getPaymentData($company),
                $subject::DATA_SCOPE_PRODUCTION_LOCATION  => $subject->getProductionLocation($company),
                $subject::COMPANY_LOGO_SETTING => $subject->getCompanyLogoSetting($company),
                $subject::IS_EPRO_U2Q_ENABLED => $subject->isEproU2QEnabled($company),
                $subject::NOTIFICATION_BANNER_CONFIG => $subject->getNotificationBannerData($company),
                $subject::DATA_SCOPE_FXO_WEB_ANALYTICS => $subject->getFxoWebAnalyticsData($company),
                $subject::CUSTOM_BILLING_INVOICED => $this->invoicedMapper->fromJson(
                    (string)$company->getData($subject::CUSTOM_BILLING_INVOICED)
                )->getItemsArray(),
                $subject::CUSTOM_BILLING_CREDIT_CARD => $this->creditCardMapper->fromJson(
                    (string)$company->getData($subject::CUSTOM_BILLING_CREDIT_CARD)
                )->getItemsArray(),
                $subject::CUSTOM_BILLING_SHIPPING => $this->shippingMapper->fromJson(
                    (string)$company->getData($subject::CUSTOM_BILLING_SHIPPING)
                )->getItemsArray(),
            ];
            $result['id'] = $company->getId();
            unset($result[$subject::DATA_SCOPE_COMPANYSELFREGDATA]);

            return $result;
    }
}
