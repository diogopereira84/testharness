<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company;

use Fedex\Punchout\Api\Data\ConfigInterface as PunchoutConfigInterface;
use Magento\Backend\Model\Session;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Controller\Adminhtml\Index\Save as CompanyController;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\Company\Custom\Billing\Invoiced\Mapper as InvoicedMapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper as CreditCardMapper;
use Fedex\Company\Model\Company\Custom\Billing\Shipping\Mapper as ShippingMapper;
use Fedex\Company\Model\CompanyData;
use Fedex\SelfReg\Model\CompanySelfRegDataFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\Company\DataProvider as CompanyDataProvider;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AttributeMetadataResolver;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Magento\Framework\UrlInterface;

/**
 * Data provider for company.
 */
class DataProvider extends CompanyDataProvider
{
    const DATA_SCOPE_AUTHENTICATION = 'authentication_rule';
    const DATA_SCOPE_SHIPPINGOPTIONS = 'shipping_options';
    const DATA_SCOPE_INTERNALSETTINGS = 'internal_settings';
    const DATA_SCOPE_GLOBALSETTINGS = 'global_settings';
    const DATA_SCOPE_EMAILNOTIFICATION = 'email_notf_options';
    const DATA_SCOPE_CATALOGANDDOCUMENT = 'catalog_document';
    const DATA_SCOPE_UPLOADTOQUOTE = 'upload_to_quote';
    const SHARED_CATALOG_ID = 'shared_catalog_id';
    const DATA_SCOPE_PAYMENTMETHODS = 'payment_methods';
    const DATA_SCOPE_CXMLNOTIFICATION = 'cxml_notification';
    const DATA_SCOPE_HOMEPAGESETTINGS = 'homepage_settings';
    const DATA_SCOPE_COMPANYSELFREGDATA = 'self_reg_login';
    const DATA_SCOPE_ADDITIONALDATA = 'company_payment_methods';
    const DATA_SCOPE_PRODUCTION_LOCATION = 'production_location';
    const DATA_SCOPE_FXO_WEB_ANALYTICS = 'fxo_web_analytics';
    const CUSTOM_BILLING_INVOICED = 'custom_billing_invoiced';
    const CUSTOM_BILLING_CREDIT_CARD = 'custom_billing_credit_card';
    const CUSTOM_BILLING_SHIPPING = 'custom_billing_shipping';
    const COMPANY_LOGO_SETTING = 'company_logo';
    const IS_EPRO_U2Q_ENABLED ='is_epro_u2q_enabled';

    // Notification Banner Configuration Const var
    const NOTIFICATION_BANNER_CONFIG = 'notification_banner_config';
    const IS_BANNER_ENABLE = 'is_banner_enable';
    const BANNER_TITLE = 'banner_title';
    const ICONOGRAPHY = 'iconography';
    const BANNER_DESCRIPTION = 'description';
    const BANNER_CTA_TEXT = 'cta_text';
    const BANNER_CTA_LINK = 'cta_link';
    const BANNER_LINK_OPEN_IN_NEW_TAB = 'link_open_in_new_tab';

    /**
     * DataProvider constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param CompanyCollectionFactory $companyCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CustomerRepositoryInterface $customerRepository
     * @param Config $eavConfig
     * @param AttributeMetadataResolver $attributeMetadataResolver
     * @param Json $json
     * @param CompanyData $companyData
     * @param CompanySelfRegDataFactory $companySelfRegDataFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param ProductionLocationFactory $productionlocationFactory
     * @param ToggleConfig $toggleConfig
     * @param ConfigInterface $configInterface
     * @param RequestInterface $request
     * @param Session $adminSession
     * @param AdminConfigHelper $orderApprovalB2BHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CompanyCollectionFactory $companyCollectionFactory,
        protected JoinProcessorInterface $extensionAttributesJoinProcessor,
        protected CustomerRepositoryInterface $customerRepository,
        protected Config $eavConfig,
        protected AttributeMetadataResolver $attributeMetadataResolver,
        private Json $json,
        private UrlInterface $urlBuilder,
        private CompanyData $companyData,
        private CompanySelfRegDataFactory $companySelfRegDataFactory,
        private GroupRepositoryInterface $groupRepository,
        private StoreRepositoryInterface $storeRepository,
        private CompanyRepositoryInterface $companyRepository,
        private ProductionLocationFactory $productionlocationFactory,
        private ToggleConfig $toggleConfig,
        private ConfigInterface $configInterface,
        private RequestInterface $request,
        private Session $adminSession,
        private AdminConfigHelper $orderApprovalB2BHelper,
        private AuthDynamicRowsFactory $authDynamicRowsFactory,
        private readonly InvoicedMapper $invoicedMapper,
        private readonly CreditCardMapper $creditCardMapper,
        private readonly ShippingMapper $shippingMapper,
        private readonly PunchoutConfigInterface $punchoutConfigInterface,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $companyCollectionFactory,
            $extensionAttributesJoinProcessor,
            $customerRepository,
            $eavConfig,
            $attributeMetadataResolver,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        if ($this->getName() == 'company_form_data_source') {
            $meta['upload_to_quote']['children']['upload_to_quote_next_step_content']['arguments']['data']['config']['default'] = $this->getDefaultUploadToQuoteNextStepContent();
        }

        return $meta;
    }

    public function getSharedCatalogId($company){
        return $company->getSharedCatalogId();
    }

    /**
     * getAuthenticationData.
     * @param CompanyInterface $company
     * @return array AuthenticationData
     */
    public function getAuthenticationData(CompanyInterface $company)
    {
        $companySelfRegData = $this->getCompanySelfRegData($company);
        $authenticationData = [
            'storefront_login_method' => $company->getStorefrontLoginMethodOption() ?? '',
            'sso_login_url' => $company->getSsoLoginUrl() ?? '',
            'sso_logout_url' => $company->getSsoLogoutUrl() ?? '',
            'sso_idp' => $company->getSsoIdp() ?? '',
            'self_reg_login_method' => $companySelfRegData['self_reg_login_method'] ?? '',
            'domains' => $companySelfRegData['domains'] ?? '',
            'error_message' => $companySelfRegData['error_message'] ?? '',
            'fcl_user_email_verification_error_message' => $companySelfRegData['fcl_user_email_verification_error_message'] ?? '',
            'fcl_user_email_verification_user_display_message' => $companySelfRegData['fcl_user_email_verification_user_display_message'] ?? '',
            'acceptance_option' => $company->getAcceptanceOption(),
            'hidden_auth_flag' => $this->getAuthRuleFlag($company),
            'domain_name' => $company->getDomainName(),
            'network_id' => $company->getNetworkId(),
            'site_name' => $company->getSiteName(),
        ];
        if ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')) {
            $authenticationData['sso_group'] = $company->getSsoGroup() ?? '';
        }

        return $authenticationData;
    }

    /**
     * getShippingOptionsData.
     * @param CompanyInterface $company
     * @return array ShippingOptionsData
     */
    public function getShippingOptionsData(CompanyInterface $company)
    {
        $allowedDeliveryOption = $company->getAllowedDeliveryOptions() === '' ? '[]'
        : $company->getAllowedDeliveryOptions();

        return [
            'is_delivery' => $company->getIsDelivery(),
            'is_pickup' => $company->getIsPickup(),
            'hc_toggle' => $company->getHcToggle(),
            'recipient_address_from_po' => $company->getRecipientAddressFromPo(),
            CompanyController::ALLOWED_DELIVERY_OPTIONS => $this->json->unserialize($allowedDeliveryOption),
        ];
    }

    /**
     * getGlobalSettingsData.
     * @param CompanyInterface $company
     * @return array sitename
     */
    public function getGlobalSettingsData(CompanyInterface $company)
    {
        return [
            'site_name' => $company->getSiteName(),
        ];
    }

    /**
     * getAuthRuleFlag.
     * @param CompanyInterface $company
     * @return boolean
     * @codeCoverageIgnore
     */
    public function getAuthRuleFlag(CompanyInterface $company)
    {
        $id = $company->getId();
        $acceptanceRule = $company->getAcceptanceOption();
        if (isset($acceptanceRule) && (($acceptanceRule == 'both') || ($acceptanceRule == 'extrinsic'))) {
            $collection = $this->authDynamicRowsFactory->create()->getCollection()
            ->addFieldToSelect('*')->addFieldToFilter('company_id', ['eq' => $id])
            ->addFieldToFilter('type', ['like' => 'extrinsic']);
            if (count($collection) > 0) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * getGeneralData.
     * @param CompanyInterface $company
     * @return array EmailNotificationData
     */
    public function getEmailNotificationData(CompanyInterface $company)
    {
        return [
            'is_quote_request' => $company->getIsQuoteRequest(),
            'is_expiring_order' => $company->getIsExpiringOrder(),
            'is_expired_order' => $company->getIsExpiredOrder(),
            'is_order_reject' => $company->getIsOrderReject(),
            'is_success_email_enable' => $company->getIsSuccessEmailEnable(),
            'bcc_comma_seperated_email' => $company->getBccCommaSeperatedEmail(),
            'order_confirmation_email_template' => $company->getOrderConfirmationEmailTemplate(),
        ];
    }

    /**
     * getCatalogAndDocumentsData.
     * @param CompanyInterface $company
     * @return array
     */
    public function getCatalogAndDocumentsData(CompanyInterface $company)
    {
        $defaultCatalogAndDocumentsArray = [
            'allow_own_document' => $company->getAllowOwnDocument(),
            'allow_shared_catalog' => $company->getAllowSharedCatalog(),
            'allow_upload_and_print' => $company->getAllowUploadAndPrint(),
            'allow_non_standard_catalog' => $company->getAllowNonStandardCatalog(),
            'non_standard_catalog_distribution_list' => $company->getNonStandardCatalogDistributionList(),
            'office_supplies_enabled' => $company->getOfficeSuppliesEnabled(),
            'shipping_packing_mailing_enabled' => $company->getShippingPackingMailingEnabled(),
            'box_enabled' => $company->getBoxEnabled(),
            'dropbox_enabled' => $company->getDropboxEnabled(),
            'google_enabled' => $company->getGoogleEnabled(),
            'microsoft_enabled' => $company->getMicrosoftEnabled(),
            'shared_catalog_id' => $this->getSharedCatalogId($company)
        ];

        $additionalData = $this->companyData->getAdditionalData($company->getId());
        if ($additionalData) {
            $isReorderEnabled = $additionalData['is_reorder_enabled'] ?? false;
            $defaultCatalogAndDocumentsArray['is_reorder_enabled'] = $isReorderEnabled;
            $defaultCatalogAndDocumentsArray['all_print_products_cms_block_identifier'] =
                $additionalData['all_print_products_cms_block_identifier'] ?? '';
        }
        return $defaultCatalogAndDocumentsArray;
    }

    /**
     * Get upload to quote settins data
     *
     * @param CompanyInterface $company
     * @return array defaultUploadToQuoteArray
     */
    public function getUploadToQuoteData(CompanyInterface $company)
    {
        $defaultUploadToQuoteArray = [];

        $isUploadToQuoteToggle = $this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote');
        if ($isUploadToQuoteToggle) {
            $defaultUploadToQuoteArray['allow_upload_to_quote'] = $company->getAllowUploadToQuote();
            $defaultUploadToQuoteArray['allow_next_step_content'] = $company->getAllowNextStepContent();
            $defaultUploadToQuoteArray['upload_to_quote_next_step_content'] =
            $company->getUploadToQuoteNextStepContent();
        }

        return $defaultUploadToQuoteArray;
    }

    /**
     * getPaymentMethodsData.
     * @param CompanyInterface $company
     * @return array PaymentMethodsData
     */
    public function getPaymentMethodsData(CompanyInterface $company)
    {
        return [
            'payment_option' => $company->getPaymentOption(),
            'fedex_account_number' => $company->getFedexAccountNumber(),
            'shipping_account_number' => $company->getShippingAccountNumber(),
            'discount_account_number' => $company->getDiscountAccountNumber(),
        ];
    }

    /**
     * getCxmlNotificationData.
     * @param CompanyInterface $company
     * @return array CxmlNotificationData
     */
    public function getCxmlNotificationData(CompanyInterface $company)
    {
        return [
            'order_complete_confirm' => $company->getOrderCompleteConfirm(),
            'shipnotf_delivery' => $company->getShipnotfDelivery(),
            'order_cancel_customer' => $company->getOrderCancelCustomer(),
        ];
    }

    /**
     * getHomepageSettingsData
     *
     * B-1145880 | Setting to show ePro Home Page
     * @param CompanyInterface $company
     * @return array HomepageSettingsData
     */
    public function getHomepageSettingsData(CompanyInterface $company)
    {
        return [
            'enable_upload_section' => $company->getEnableUploadSection(),
            'enable_catalog_section' => $company->getEnableCatalogSection(),
        ];
    }

    /**
     * Get company self reg data
     *
     * @return array
     */
    public function getCompanySelfRegData(CompanyInterface $company)
    {
        if ($company && $company->getSelfRegData() !== null) {
            $companySelfRegData = $this->json->unserialize($company->getSelfRegData());

            return [
                'enable_selfreg' => $companySelfRegData['enable_selfreg'],
                'self_reg_login_method' => $companySelfRegData['self_reg_login_method'],
                'domains' => $companySelfRegData['domains'],
                'error_message' => $companySelfRegData['error_message'],
                'fcl_user_email_verification_error_message' => isset($companySelfRegData['fcl_user_email_verification_error_message']) ? $companySelfRegData['fcl_user_email_verification_error_message'] : null,
                'fcl_user_email_verification_user_display_message' => isset($companySelfRegData['fcl_user_email_verification_user_display_message']) ? $companySelfRegData['fcl_user_email_verification_user_display_message'] : null,
            ];
        }

        return [];
    }

    /**
     * Get store and store view name
     * B-1205796 : API integration for CC details and Billing details in Magento Admin
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function getStoreDetails(CompanyInterface $company)
    {
        $additionalData = $this->companyData->getAdditionalData($company->getId());
        if ($additionalData) {
            $storeId = $additionalData['store_id'] ?? '';
            $storeViewId = $additionalData['store_view_id'] ?? '';
            if ($storeId) {
                $additionalData['store_name'] = $this->groupRepository->get($storeId)->getName();
            }
            if ($storeViewId) {
                $additionalData['store_view_name'] = $this->storeRepository->getById($storeViewId)->getName();
            }
        }

        return $additionalData;
    }

    public function getNewStoreDetails(CompanyInterface $company)
    {
        $additionalData = $this->companyData->getAdditionalData($company->getId());
        if ($additionalData) {
            $storeId = $additionalData['new_store_id'] ?? '';
            $storeViewId = $additionalData['new_store_view_id'] ?? '';
            if ($storeId) {
                $additionalData['new_store_name'] = $this->groupRepository->get($storeId)->getName();
            }
            if ($storeViewId) {
                $additionalData['new_store_view_name'] = $this->storeRepository->getById($storeViewId)->getName();
            }
        }

        return $additionalData;
    }

    /**
     * Get Payament Data
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function getPaymentData(CompanyInterface $company)
    {
        $additionalData = $this->companyData->getAdditionalData($company->getId());
        if ($additionalData) {
            $compPaymentOptions = $additionalData['company_payment_options'] ?
            $this->json->unserialize($additionalData['company_payment_options'])
            : [];
            return [
                'company_payment_options' => $compPaymentOptions,
                'fedex_account_options' => $additionalData['fedex_account_options'],
                'creditcard_options' => $additionalData['creditcard_options'],
                'default_payment_method' => $additionalData['default_payment_method'],
                'fxo_account_number' => $company->getFedexAccountNumber(),
                'fxo_shipping_account_number' => $company->getShippingAccountNumber(),
                'fxo_discount_account_number' => $company->getDiscountAccountNumber(),
                'fxo_account_number_editable' => $company->getFxoAccountNumberEditable(),
                'shipping_account_number_editable' => $company->getShippingAccountNumberEditable(),
                'discount_account_number_editable' => $company->getDiscountAccountNumberEditable(),
            ];
        }

        return [];
    }

     /**
      * Get Production Location
      *
      * @param CompanyInterface $company
      * @return array
      * @throws NoSuchEntityException
      */
    public function getProductionLocation(CompanyInterface $company)
    {
        if ($company->getId()) {
            $company = $this->companyRepository->get($company->getId());
            $allowProductionLocations = $company->getAllowProductionLocation();
            $productionLocation = $this->productionlocationFactory->create();
            $collection = $productionLocation->getCollection()->addFieldToFilter('company_id', $company->getId());
            $additionalData = $this->companyData->getAdditionalData($company->getId());

            if ($additionalData) {
                if ($this->toggleConfig->getToggleConfigValue('explorers_d195445_fix')) {
                    $productionLocationTabData = [
                        'allow_production_location' => $allowProductionLocations ?? 0,
                        'is_restricted' => count($collection->getData()) ?? 0,
                        'production_location_option' => $company->getData('production_location_option') ?? 'recommended_location_all_locations'
                    ];
                } else {
                    $productionLocationTabData = [
                        'allow_production_location' => $allowProductionLocations ?? 0,
                        'is_restricted' => count($collection->getData()) ?? 0,
                        'production_location_option' => 'recommended_location_all_locations'
                    ];
                }

                $productionLocationTabData = $this->getDiscountConfigurations(
                    $productionLocationTabData,
                    $additionalData
                );

                return $this->getOrderNotes($productionLocationTabData, $additionalData);
            }
        }
        return [];
    }

    /**
     * Get Discount configurations data
     *
     * @param array $productionLocationTabData
     * @param array $additionalData
     *
     * @return array $productionLocationTabData
     */
    public function getDiscountConfigurations($productionLocationTabData, $additionalData)
    {
        $productionLocationTabData['is_promo_discount_enabled'] = $additionalData['is_promo_discount_enabled'];
        $productionLocationTabData['is_account_discount_enabled']= $additionalData['is_account_discount_enabled'];
        $productionLocationTabData['epro_new_platform_order_creation'] =
            $additionalData['epro_new_platform_order_creation'] ?? 0;
        $productionLocationTabData['is_b2b_order_approval_enabled'] =
            $additionalData['is_b2b_order_approval_enabled'] ?? 0;

        return $productionLocationTabData;
    }

    /**
     * Get Order Notes
     *
     * @param array $productionLocationTabData
     * @param array $additionalData
     *
     * @return array $productionLocationTabData
     */
    public function getOrderNotes($productionLocationTabData, $additionalData)
    {
        $productionLocationTabData['order_notes'] = $additionalData['order_notes'];
        $productionLocationTabData['terms_and_conditions'] = $additionalData['terms_and_conditions'];

        return $productionLocationTabData;
    }

    /**
     * Get company logo settings
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function getCompanyLogoSetting(CompanyInterface $company)
    {
        $resultArray = [];
        $additionalData = $this->companyData->getAdditionalData($company->getId());
        $resultArray = [
            'image_field' => $company->getCompanyLogo() ? [json_decode($company->getCompanyLogo(), true)] : ''
        ];
        $resultArray['homepage_cms_block_identifier'] = $additionalData['homepage_cms_block_identifier']??'';
        return $resultArray;
    }

    /**
     * Get company MVP catalog settings
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function isMvpCatalogEnabled(CompanyInterface $company)
    {
        return [
            'is_catalog_mvp_enabled' => $company->getIsCatalogMvpEnabled()
        ];
    }

    /**
     * Get Notification Banner Data
     *
     * @param object $company
     * @return array
     */
    public function getNotificationBannerData($company)
    {
        $additionalData = $this->companyData->getAdditionalData($company->getId());

        return [
            self::IS_BANNER_ENABLE => $additionalData[self::IS_BANNER_ENABLE],
            self::BANNER_TITLE => $additionalData[self::BANNER_TITLE],
            self::ICONOGRAPHY => $additionalData[self::ICONOGRAPHY],
            self::BANNER_DESCRIPTION => $additionalData[self::BANNER_DESCRIPTION],
            self::BANNER_CTA_TEXT => $additionalData[self::BANNER_CTA_TEXT],
            self::BANNER_CTA_LINK => $additionalData[self::BANNER_CTA_LINK],
            self::BANNER_LINK_OPEN_IN_NEW_TAB => $additionalData[self::BANNER_LINK_OPEN_IN_NEW_TAB]
        ];
    }

    /**
     * getFxoWebAnalyticsData.
     * @param CompanyInterface $company
     * @return array FxoWebAnalyticsData
     */
    public function getFxoWebAnalyticsData(CompanyInterface $company)
    {
        return [
            'content_square' => $company->getContentSquare(),
            'adobe_analytics' => $company->getAdobeAnalytics(),
            'app_dynamics' => $company->getAppDynamics(),
            'forsta' => $company->getForsta(),
        ];
    }

    /**
     * Get company Epro Return settings
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function isEproU2QEnabled(CompanyInterface $company)
    {
        return [
            'is_epro_u2q_enabled' => $company->getData('is_epro_u2q_enabled')
        ];
    }

    /**
     * Get default content for Upload to Quote Next Step
     *
     * @param string|null $companyUrlExtension
     * @return string
     */
    private function getDefaultUploadToQuoteNextStepContent($companyUrlExtension = null): string
    {
        if($companyUrlExtension) {
            $dynamicUrl = $this->urlBuilder->getBaseUrl() . "ondemand/{$companyUrlExtension}/uploadtoquote/index/quotehistory/";
        } else {
            // Fallback for missing company
            $dynamicUrl = $this->urlBuilder->getBaseUrl() . "ondemand/default/uploadtoquote/index/quotehistory/";
        }
        return  sprintf(
            '
                <p>1 A FedEx Office team member will review your request and generate a quote within 4 business hours.</p>
                <p>2 You will receive an email notification that your quote is ready for review. To check status, go to
                    <a href="%s" target="_blank" rel="noopener noreferrer">
                        <strong>My Quotes</strong>
                    </a>.
                </p>
                <p>3 Review and approve your quote to submit your order for production.</p>
            ',
            $dynamicUrl
        );
    }

    /**
     * Get company data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        foreach ($data as $key=>$datum) {
            if(empty($data[$key]['upload_to_quote']['upload_to_quote_next_step_content'])){
                $data[$key]['upload_to_quote']['upload_to_quote_next_step_content'] = $this->getDefaultUploadToQuoteNextStepContent($data[$key]['general']['company_url_extention']);
            }
        }
        return $data;
    }
}
