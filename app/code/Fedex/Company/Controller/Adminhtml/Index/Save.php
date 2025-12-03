<?php

/**
 * @Author Pratibha
 * Override company save functionality to addon custom changes
 */

namespace Fedex\Company\Controller\Adminhtml\Index;

use Exception;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Helper\ExportCompanyData;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\CompanyCreation;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Model\CompanySelfRegDataFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\CompanySuperUserGet;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Fedex\Company\Helper\Data;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\Shipto\Controller\Adminhtml\Plocation\Save as RestrictLocationSave;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Session;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * Company save controller.
 */
class Save implements HttpPostActionInterface
{
    public const CREATE_NEW_VALUE = 'create_new';

    /**
     * Custom billing invoiced field key
     */
    private const CUSTOM_BILLING_INVOICED = 'custom_billing_invoiced';

    /**
     * Custom billing credit card field key
     */
    private const CUSTOM_BILLING_CREDIT_CARD = 'custom_billing_credit_card';

    /**
     * Custom billing shipping field key
     */
    private const CUSTOM_BILLING_SHIPPING = 'custom_billing_shipping';

    /**
     * Restricted Recommended Toggle key
     */
    public const EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION = 'explorers_restricted_and_recommended_production';

    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Company::manage';
    const IS_EPRO_ENABLED = 'is_epro_enabled';
    const DOMAIN_NAME = 'domain_name';
    const NETWORK_ID = 'network_id';
    const COMPANY_URL = 'company_url';
    const ACCEPTANCE_OPTION = 'acceptance_option';
    const RULE_CODE_E = 'rule_code_e';
    const RULE_CODE_C = 'rule_code_c';
    const IS_DELIVERY = 'is_delivery';
    const IS_PICKUP = 'is_pickup';
    const HC_TOGGLE = 'hc_toggle';
    const RULE_LOAD = 'rule_load';
    const SITE_NAME = 'site_name';
    const IS_QUOTE_REQUEST = "is_quote_request";
    const IS_EXPIRING_ORDER = "is_expiring_order";
    const IS_EXPIRED_ORDER = "is_expired_order";
    const IS_ORDER_REJECT = "is_order_reject";
    const ALLOW_OWN_DOCUMENT = "allow_own_document";
    const ALLOW_SHARED_CATALOG = "allow_shared_catalog";
    const ALLOW_NON_STANDARD_CATALOG = "allow_non_standard_catalog";
    const NON_STANDARD_CATALOG_DISTRIBUTION_LIST = "non_standard_catalog_distribution_list";
    const ALLOW_UPLOAD_TO_QUOTE = "allow_upload_to_quote";
    public const ALLOW_NEXT_STEP_CONTENT  = "allow_next_step_content";
    public const UPLOAD_TO_QUOTE_NEXT_STEP_CONTENT = "upload_to_quote_next_step_content";
    const SHARED_CATALOG_ID = "shared_catalog_id";
    const OFFICE_SUPPLIES_ENABLED = "office_supplies_enabled";
    const SHIPPING_PACKING_MAILING_ENABLED = "shipping_packing_mailing_enabled";
    const BOX_ENABLED = "box_enabled";
    const DROPBOX_ENABLED = "dropbox_enabled";
    const GOOGLE_ENABLED = "google_enabled";
    const MICROSOFT_ENABLED = "microsoft_enabled";
    const PAYMENT_OPTION = 'payment_option';
    const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    const DISCOUNT_ACCOUNT_NUMBER = 'discount_account_number';
    const SHIPPING_ACCOUNT_NUMBER = 'shipping_account_number';
    const ORDER_COMPLETE_CONFIRM = 'order_complete_confirm';
    const SHIPNOTF_DELIVERY = 'shipnotf_delivery';
    const ORDER_CANCEL_CUSTOMER = 'order_cancel_customer';
    const RECIPIENT_ADDRESS_FROM_PO = 'recipient_address_from_po';
    const PRODUCTION_LOCATION_OPTION = 'production_location_option';
    const IS_RESTRICTED = 'is_restricted';
    const ALLOWSITEMEMBERS = 'allow_production_location';
    const ENABLE_UPLOAD_SECTION = 'enable_upload_section';
    const ENABLE_CATALOG_SECTION = 'enable_catalog_section';
    const ALLOWED_DELIVERY_OPTIONS = 'allowed_delivery_options';
    const FEDEX_ACCOUNT_OPTIONS = 'fedex_account_options';
    const CREDITCARD_OPTIONS = 'creditcard_options';
    const IMAGE_FIELD = 'image_field';
    const IS_SENSETIVE_DATA_ENABLED = 'is_sensitive_data_enabled';
    const COMPANY_URL_EXTENTION = 'company_url_extention';
    const IS_PROMO_DISCOUNT_ENABLED = 'is_promo_discount_enabled';
    const IS_ACCOUNT_DISCOUNT_ENABLED = 'is_account_discount_enabled';
    const EPRO_NEW_PLATFORM_ORDER_CREATION = 'epro_new_platform_order_creation';
    const ORDER_NOTES = 'order_notes';
    const IS_CATALOG_MVP_ENABLED = 'is_catalog_mvp_enabled';
    const IS_REORDER_ENABLED = 'is_reorder_enabled';
    const TERMS_AND_CONDITIONS = 'terms_and_conditions';
    const CONTENT_SQUARE = 'content_square';
    const ADOBE_ANALYTICS = 'adobe_analytics';
    const APP_DYNAMICS = 'app_dynamics';
    const FORSTA = 'forsta';
    const NUANCE = 'nuance';
    // Notification Banner Configuration Const var
    const IS_BANNER_ENABLE = 'is_banner_enable';
    const BANNER_TITLE = 'banner_title';
    const ICONOGRAPHY = 'iconography';
    const BANNER_DESCRIPTION = 'description';
    const BANNER_CTA_TEXT = 'cta_text';
    const BANNER_CTA_LINK = 'cta_link';
    const BANNER_LINK_OPEN_IN_NEW_TAB = 'link_open_in_new_tab';
    const ORDER_CONFIRMATION_EMAIL = "is_success_email_enable";
    const BCC_COMMA_SEPERATED_EMAIL = "bcc_comma_seperated_email";
    const ORDER_CONFIRMATION_EMAIL_TEMPLATE = "order_confirmation_email_template";
    const ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER = 'all_print_products_cms_block_identifier';
    const HOMEPAGE_CMS_BLOCK_IDENTIFIER = 'homepage_cms_block_identifier';
    public const IS_B2B_ORDER_APPROVAL_ENABLED = 'is_b2b_order_approval_enabled';
    const ALLOW_UPLOAD_AND_PRINT = 'allow_upload_and_print';
    const IS_EPRO_U2Q_ENABLED ='is_epro_u2q_enabled';

    /**
     * @var CompanySelfRegData $companySelfRegDataFactory
     */
    private $companySelfRegDataFactory;

    /**
     * Save constructor
     *
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CompanySuperUserGet $superUser
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterfaceFactory $companyDataFactory
     * @param AuthDynamicRowsFactory $ruleFactory
     * @param ResourceConnection $resourceConnection
     * @param DataObjectHelper $dataObjectHelper
     * @param ToggleConfig $toggleConfig
     * @param Json $json
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanySelfRegDataFactory $companySelfRegDataFactory
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param Data $companyHelper
     * @param ConfigInterface $configInterface
     * @param CompanyCreation $companyCreation
     * @param AdminConfigHelper $adminConfigHelper
     * @param BlockFactory $blockFactory
     * @param RestrictLocationSave $restrictLocationSave
     * @param CustomerSession $customerSession
     * @param Session $session
     * @param EventManager $eventManager
     * @param GroupManagementInterface $groupManagement
     */
    public function __construct(
        private RequestInterface     $request,
        protected ManagerInterface $messageManager,
        protected RedirectFactory $resultRedirectFactory,
        private DataObjectProcessor $dataObjectProcessor,
        private CompanySuperUserGet $superUser,
        private CompanyRepositoryInterface $companyRepository,
        private CompanyInterfaceFactory $companyDataFactory,
        protected AuthDynamicRowsFactory $ruleFactory,
        protected ResourceConnection $resourceConnection,
        private DataObjectHelper $dataObjectHelper,
        private ToggleConfig $toggleConfig,
        private Json $json,
        private AdditionalDataFactory $additionalDataFactory,
        CompanySelfRegDataFactory $companySelfRegDataFactory,
        protected LoggerInterface $logger,
        protected TimezoneInterface $timezone,
        protected Data $companyHelper,
        protected ConfigInterface $configInterface,
        private CompanyCreation $companyCreation,
        protected AdminConfigHelper $adminConfigHelper,
        public readonly BlockFactory $blockFactory,
        private RestrictLocationSave $restrictLocationSave,
        private CustomerSession $customerSession,
        private Session $session,
        protected EventManager $eventManager,
        private readonly GroupManagementInterface $groupManagement
    ) {
        $this->companySelfRegDataFactory = $companySelfRegDataFactory;
    }

    /**
     * Store Customer Group Data to session.
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function storeCompanyDataToSession(CompanyInterface $company)
    {
        $companyData = $this->dataObjectProcessor->buildOutputDataArray(
            $company,
            CompanyInterface::class
        );
        $this->session->setCompanyData($companyData);
    }

    /**
     * Create or save customer group.
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var CompanyInterface $company */
        $company = null;
        $boolSetFlag = false;
        $id = $this->request->getParam('id') ? $this->request->getParam('id') : null;

        try {
            $data = $this->request->getParam('general');
            $authenticationRuleData = $this->request->getParam('authentication_rule');
            /*Get all request data*/
            $requestData = $this->extractData();
            $urlExtensionName = $requestData['company_url_extention'];

            /* Validate for unique domain id */
            $validateNewtworkId = $this->companyHelper->validateNewtworkId($authenticationRuleData['network_id'], $id);
            /* Validate for unique domain name */
            $validateCompanyName = $this->companyHelper->validateCompanyName($data['company_name'], $id);
            /* Validate for unique url extension */
            $validateCompanyUrlExt = $this->companyHelper->isCompanyUrlExtentionDuplicate($requestData['company_url_extention'], $id);

            $boolSetFlag = $this->companyValidationMsg(
                $validateNewtworkId,
                $validateCompanyName,
                $validateCompanyUrlExt,
                $data,
                $authenticationRuleData
            );
            /* Validate min & max extrinsic rule */
            if (!$boolSetFlag) {
                $response = $this->executeRefactor($this->request);
            }
            if ($this->configInterface->getE414712HeroBannerCarouselForCommercial()) {
                $boolSetFlag = $this->homepageCMSValidation($this->request->getParam('company_logo'));
            }

            if ($boolSetFlag || $response) {
                $boolSetFlag = false;
                $returnToEdit = true;
                return $this->getRedirectToEditOrNew($returnToEdit, $id);
            }

            $company = $this->saveCompany($id);
            $this->handleCompanyEntitiesCreationAsync($id, $requestData, $urlExtensionName, $company->getId());
            $this->saveAdditionalData($company->getId());

            //To Remove once Cleanup Company Toggle in removed
            $companySelfRegData = $this->request->getParam('self_reg_login');
            $authRuleData = $this->request->getParam('authentication_rule');
            if ($companySelfRegData || $authRuleData) {
                $this->saveCompanySelfRegData($companySelfRegData, $authRuleData, $company->getId());
            }

            // After save
            $this->eventManager->dispatch(
                'adminhtml_company_save_after',
                ['company' => $company, 'request' => $this->request]
            );

            $companyData = ['companyName' => $company->getCompanyName()];
            $this->messageManager->addSuccessMessage(
                $id
                    ? __('You have saved company %companyName.', $companyData)
                    : __('You have created company %companyName.', $companyData)
            );
            $returnToEdit = (bool) $this->request->getParam('back', false);
        } catch (LocalizedException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $returnToEdit = true;
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($company instanceof CompanyInterface) {
                $this->storeCompanyDataToSession($company);
            }
            if(isset($requestData) && $this->checkRequestDataCreateAllNewEntities($id, $requestData)) {
                $this->companyCreation->deleteEntitiesCreatedDuringCompanyFlow();
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Something went wrong. Please try again later.');
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $returnToEdit = true;
            $this->messageManager->addExceptionMessage($e, __('Something went wrong. Please try again later.'));
            if ($company instanceof CompanyInterface) {
                $this->storeCompanyDataToSession($company);
            }
            if(isset($requestData) && $this->checkRequestDataCreateAllNewEntities($id, $requestData)) {
                $this->companyCreation->deleteEntitiesCreatedDuringCompanyFlow();
            }
        }
        $params= $this->request->getParams();
        if(isset($params['general']['new_compnay_location_id'])&& !empty($params['general']['new_compnay_location_id']))
        {
            $locationdata['is_restricted_product_location_toggle']=$this->toggleConfig->getToggleConfigValue('explorers_restricted_and_recommended_production');
            $locationdata['is_recommended_store']=$params['production_location']['production_location_option'];
            $locationdata['company_id']=$company->getId();
            $locationIds = explode(",", $params['general']['new_compnay_location_id']);
            $getAllLocationsFromSession = $this->customerSession->getAllLocations();
            $getAllLocationsFromSession = $this->restrictLocationSave->jsonToArray($getAllLocationsFromSession);
            $keyArray = $this->restrictLocationSave->prepareKeyArray($locationIds, $getAllLocationsFromSession);
            $this->restrictLocationSave->saveLocation($keyArray, $locationdata);
        }
        return $this->getRedirect($returnToEdit, $company);
    }

    /**
     * This will submit company entities creation to queue
     * @param $id
     * @param $requestData
     * @param $urlExtensionName
     * @param $companyId
     * @return void
     */
    private function handleCompanyEntitiesCreationAsync($id, $requestData, $urlExtensionName, $companyId): void
    {
        if (is_null($id)) {
            $publishMessage = false;
            $messageContentArray = [
                'company_id' => $companyId,
                'request_params' => $this->request->getParams(),
                'url_extension_name' => $urlExtensionName,
            ];
            if ($this->checkRequestDataCreateAllNewEntities($id, $requestData)) {
                $publishMessage = true;
                $messageContentArray['creation_type'] = 'all';
            } elseif ($this->isOnlyCreateRootCategory($id, $requestData)) {
                $publishMessage = true;
                $messageContentArray['creation_type'] = 'root_category';
                $messageContentArray['customer_group_id'] = $requestData['customer_group_id'];
            } elseif ($this->isOnlyCreateCustomerGroup($id, $requestData)) {
                $publishMessage = true;
                $messageContentArray['creation_type'] = 'customer_group';
                $messageContentArray['shared_catalog_id'] = $requestData['shared_catalog_id'];
            }
            if ($publishMessage) {
                $this->companyCreation->publishCompanyEntities($messageContentArray);
                $this->messageManager->addSuccessMessage('Company creation is in progress. Make sure to check the company details for Shared Catalog and Customer Group before using it.');
            }
        }
    }

    /**
     * Refactor company error messsage
     * @method companyValidationMsg
     * @return boolean
     */
    public function companyValidationMsg(
        $validateNewtworkId,
        $validateCompanyName,
        $validateCompanyUrlExt,
        $data,
        $authenticationRuleData
    ) {
        $boolSetFlag = false;
        if (!$validateNewtworkId) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Network id, ' . $authenticationRuleData['network_id'] . ', already assigned to some other company.');
            $this->messageManager->addError(__($authenticationRuleData['network_id'] .
                ' Network id already assigned to some other company.'));
            $boolSetFlag = true;
        }

        if (!$validateCompanyName && !$boolSetFlag) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Company, ' . __($data['company_name'] . ', already assigned to some other company.'));
            $this->messageManager->addError(__($data['company_name'] .
                ' Company already assigned to some other company.'));
            $boolSetFlag = true;
        }

        if ($validateCompanyUrlExt && !$boolSetFlag) {
            $this->messageManager->addError(__(
                'Company URL extention already assigned to some other company.'
            ));
            $boolSetFlag = true;
        }

        return $boolSetFlag;
    }
    /**
     * This method writtien purpose to resolve sonar lint issue.
     * @param object $request
     * @method executeRefactor
     * @return boolean
     */
    public function executeRefactor($request)
    {
        $auth = $this->request->getParam('authentication_rule');
        $boolSetFlag = false;
        if ($auth['storefront_login_method'] == 'commercial_store_epro') {
            $validateRule = $this->validateMinMaxRule($auth);
            if ($validateRule['error']) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . __($validateRule['msg']));
                $this->messageManager->addError(__($validateRule['msg']));
                $boolSetFlag = true;
            }

            if (!$boolSetFlag) {
                /*Validate atleast one option is checked */
                $aut = $this->request->getParam('catalog_document');
                $validateRul = $this->validateUserSettings($aut);
                if ($validateRul['error']) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . __($validateRul['msg']));
                    $this->messageManager->addError(__($validateRul['msg']));
                    $boolSetFlag = true;
                }
            }
        }

        return $boolSetFlag;
    }
    /**
     * @Athor Yogesh
     * @method getRedirectToEditOrNew function redirect to new or edit company screen
     *
     * @param string $returnToEdit
     * @param int $id
     * @return boolean
     */
    private function getRedirectToEditOrNew($returnToEdit, $id)
    {
        $resultRedirect= null;
        if ($returnToEdit) {
            $resultRedirect = $this->resultRedirectFactory->create();
            if (($id != null) && $id) {
                $resultRedirect->setPath(
                    'company/index/edit',
                    ['id' => $id]
                );
            } else {
                $resultRedirect->setPath(
                    'company/index/new'
                );
            }
        }

        return $resultRedirect;
    }

    /**
     * Get redirect object depending on $returnToEdit and is company new.
     *
     * @param bool $returnToEdit
     * @param CompanyInterface|null $company [optional]
     *
     * @return Redirect
     */
    private function getRedirect($returnToEdit, CompanyInterface $company = null)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if (($company != null) && $company->getId()) {
                $resultRedirect->setPath(
                    'company/index/edit',
                    ['id' => $company->getId()]
                );
            } else {
                $resultRedirect->setPath(
                    'company/index/new'
                );
            }
        } else {
            $resultRedirect->setPath('company/index');
        }

        return $resultRedirect;
    }

    /**
     * Filter request to get just list of fields.
     *
     * @return array
     */
    private function extractData()
    {
        $allFormFields = [
            CompanyInterface::COMPANY_ID,
            CompanyInterface::STATUS,
            CompanyInterface::NAME,
            CompanyInterface::LEGAL_NAME,
            CompanyInterface::COMPANY_EMAIL,
            CompanyInterface::EMAIL,
            CompanyInterface::VAT_TAX_ID,
            CompanyInterface::RESELLER_ID,
            CompanyInterface::COMMENT,
            CompanyInterface::STREET,
            CompanyInterface::CITY,
            CompanyInterface::COUNTRY_ID,
            CompanyInterface::REGION,
            CompanyInterface::REGION_ID,
            CompanyInterface::POSTCODE,
            CompanyInterface::TELEPHONE,
            CompanyInterface::JOB_TITLE,
            CompanyInterface::PREFIX,
            CompanyInterface::FIRSTNAME,
            CompanyInterface::MIDDLENAME,
            CompanyInterface::LASTNAME,
            CompanyInterface::SUFFIX,
            CompanyInterface::GENDER,
            CompanyInterface::CUSTOMER_GROUP_ID,
            CompanyInterface::SALES_REPRESENTATIVE_ID,
            CompanyInterface::REJECT_REASON,
            CustomerInterface::WEBSITE_ID,
            self::DOMAIN_NAME,
            self::NETWORK_ID,
            self::COMPANY_URL,
            self::ACCEPTANCE_OPTION,
            self::RULE_CODE_E,
            self::RULE_CODE_C,
            self::IS_DELIVERY,
            self::IS_PICKUP,
            self::HC_TOGGLE,
            self::RULE_LOAD,
            self::SITE_NAME,
            self::IS_QUOTE_REQUEST,
            self::IS_EXPIRING_ORDER,
            self::IS_EXPIRED_ORDER,
            self::IS_ORDER_REJECT,
            self::ALLOW_OWN_DOCUMENT,
            self::ALLOW_SHARED_CATALOG,
            self::ALLOW_NON_STANDARD_CATALOG,
            self::NON_STANDARD_CATALOG_DISTRIBUTION_LIST,
            self::ALLOW_UPLOAD_TO_QUOTE,
            self::ALLOW_NEXT_STEP_CONTENT,
            self::UPLOAD_TO_QUOTE_NEXT_STEP_CONTENT,
            self::SHARED_CATALOG_ID,
            self::OFFICE_SUPPLIES_ENABLED,
            self::SHIPPING_PACKING_MAILING_ENABLED,
            self::BOX_ENABLED,
            self::DROPBOX_ENABLED,
            self::GOOGLE_ENABLED,
            self::MICROSOFT_ENABLED,
            self::PAYMENT_OPTION,
            self::FEDEX_ACCOUNT_NUMBER,
            self::SHIPPING_ACCOUNT_NUMBER,
            self::DISCOUNT_ACCOUNT_NUMBER,
            self::ORDER_COMPLETE_CONFIRM,
            self::SHIPNOTF_DELIVERY,
            self::ORDER_CANCEL_CUSTOMER,
            self::RECIPIENT_ADDRESS_FROM_PO,
            self::PRODUCTION_LOCATION_OPTION,
            self::IS_RESTRICTED,
            self::ALLOWSITEMEMBERS,
            self::ENABLE_UPLOAD_SECTION,
            self::ENABLE_CATALOG_SECTION,
            'extension_attributes',
            self::ALLOWED_DELIVERY_OPTIONS,
            self::FEDEX_ACCOUNT_OPTIONS,
            self::CREDITCARD_OPTIONS,
            self::IMAGE_FIELD,
            self::IS_SENSETIVE_DATA_ENABLED,
            self::COMPANY_URL_EXTENTION,
            self::IS_PROMO_DISCOUNT_ENABLED,
            self::IS_ACCOUNT_DISCOUNT_ENABLED,
            self::EPRO_NEW_PLATFORM_ORDER_CREATION,
            self::IS_CATALOG_MVP_ENABLED,
            self::IS_REORDER_ENABLED,
            self::TERMS_AND_CONDITIONS,
            self::CONTENT_SQUARE,
            self::ADOBE_ANALYTICS,
            self::APP_DYNAMICS,
            self::FORSTA,
            self::NUANCE,
    	    self::IS_BANNER_ENABLE,
            self::BANNER_TITLE,
            self::ICONOGRAPHY,
            self::BANNER_DESCRIPTION,
            self::BANNER_CTA_TEXT,
            self::BANNER_CTA_LINK,
            self::BANNER_LINK_OPEN_IN_NEW_TAB,
            self::ORDER_CONFIRMATION_EMAIL,
            self::BCC_COMMA_SEPERATED_EMAIL,
            self::ORDER_CONFIRMATION_EMAIL_TEMPLATE,
            self::ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER,
            self::HOMEPAGE_CMS_BLOCK_IDENTIFIER,
            self::IS_B2B_ORDER_APPROVAL_ENABLED,
            self::ALLOW_UPLOAD_AND_PRINT,
            self::IS_EPRO_U2Q_ENABLED
        ];

        $result = [];
        $request = $this->request->getParams();
        unset($request['company_logo'][self::COMPANY_URL_EXTENTION]);
        unset($request['company_logo'][self::IS_SENSETIVE_DATA_ENABLED]);
        unset($request['general'][self::NETWORK_ID]);
        unset($request['general'][self::DOMAIN_NAME]);
        unset($request['global_settings'][self::SITE_NAME]);
        unset($request['use_default']);
        if (is_array($request)) {
            foreach ($request as $fields) {
                if (!is_array($fields)) {
                    continue;
                }
                $result = array_merge_recursive($result, $fields);
            }
        }
        $result = array_intersect_key($result, array_flip($allFormFields));

        return $result;
    }

    /**
     * Create/load company, set request data, set default role for a new company.
     *
     * @param int $id
     * @return CompanyInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function saveCompany($id)
    {
        $data = $this->extractData();

        if (is_null($id)) {
            $tempCustomerGroupId = $data['customer_group_id'] ?? null;
            $tempSharedCatalogId = $data['shared_catalog_id'] ?? null;

            $tempCustomerGroupId = $this->groupManagement->getDefaultGroup()->getId();
            $tempSharedCatalogId = null;

            $data['customer_group_id'] = $tempCustomerGroupId;
            $data['shared_catalog_id'] = $tempSharedCatalogId;
        }

        $customerData = $this->extractCustomerData();
        $customer = $this->superUser->getUserForCompanyAdmin($customerData);
        if ($id !== null) {
            $company = $this->companyRepository->get((int) $id);
        } else {
            $company = $this->companyDataFactory->create();
        }
        $this->setCompanyRequestData($company, $data);
        $company->setSuperUserId($customer->getId());
        $company->setData('domain_name', $data['domain_name']);
        $company->setData('network_id', $data['network_id']);
        $company->setData('acceptance_option', $data['acceptance_option']);
        $company->setData('is_delivery', $data['is_delivery']);
        $company->setData('is_pickup', $data['is_pickup']);
        $company->setData('hc_toggle', $data['hc_toggle']);
        $company->setData('site_name', $data['site_name']);
        $company->setData('recipient_address_from_po', $data['recipient_address_from_po']);
        $company->setData('is_quote_request', $data['is_quote_request']);
        $company->setData('is_expiring_order', $data['is_expiring_order']);
        $company->setData('is_expired_order', $data['is_expired_order']);
        $company->setData('is_order_reject', $data['is_order_reject']);
        $company->setData('is_success_email_enable', $data['is_success_email_enable']);
        if (!empty($data['is_success_email_enable'])) {
            $this->validateAndSaveBccEmail( $company, $data['bcc_comma_seperated_email']);
            $company->setData('order_confirmation_email_template', $data['order_confirmation_email_template']);
        }
        $company->setData('allow_own_document', $data['allow_own_document']);
        $company->setData('allow_shared_catalog', $data['allow_shared_catalog']);
        $company->setData('allow_upload_and_print', $data['allow_upload_and_print']);
        $isNonStandardCatalogToggle = $this->toggleConfig->getToggleConfigValue('explorers_company_setting_non_standard_catalog');
        if ($isNonStandardCatalogToggle) {
            $company->setData('allow_non_standard_catalog', $data['allow_non_standard_catalog']);
            if (!empty($data['allow_non_standard_catalog'])) {
                $this->validateAndSaveNonStandardCatalogDistributionList(
                    $company, $data['non_standard_catalog_distribution_list']);
            }
        } else {
            if (!empty($data['allow_shared_catalog'])) {
                $this->validateAndSaveNonStandardCatalogDistributionList(
                    $company, $data['non_standard_catalog_distribution_list']);
            }
        }
        $isUploadToQuoteToggle = $this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote');
        if ($isUploadToQuoteToggle) {
            $company->setData('allow_upload_to_quote', $data['allow_upload_to_quote']);
            $company->setData('allow_next_step_content', $data['allow_next_step_content']);
            $company->setData('upload_to_quote_next_step_content', $data['upload_to_quote_next_step_content']);
        }
        if(isset($data['shared_catalog_id'])) {
            $company->setData('shared_catalog_id', $data['shared_catalog_id']);
        }
        $company->setData('office_supplies_enabled', $data['office_supplies_enabled'] ?? null);
        $company->setData('shipping_packing_mailing_enabled', $data['shipping_packing_mailing_enabled'] ?? null);
        /** E-359853 | Toggle Restructuring for Cloud Drive Integration **/
        $company->setData('box_enabled', $data['box_enabled']);
        $company->setData('dropbox_enabled', $data['dropbox_enabled']);
        $company->setData('google_enabled', $data['google_enabled']);
        $company->setData('microsoft_enabled', $data['microsoft_enabled']);

        $company->setData('payment_option', $data['payment_option']);
        $company->setData('enable_upload_section', $data['enable_upload_section']);
        $company->setData('enable_catalog_section', $data['enable_catalog_section']);
        $company->setData('content_square', $data['content_square']);
        $company->setData('adobe_analytics', $data['adobe_analytics']);
        $company->setData('app_dynamics', $data['app_dynamics']);
        $company->setData('forsta', $data['forsta']);
        $company->setData('nuance', ($data['nuance'] ?? null));

        $companyUrl = 'secureStoreUrlGoesHere' . trim($data['company_url_extention']);
        $company->setData('company_url', $companyUrl);

        if(array_key_exists('is_catalog_mvp_enabled',$data)){
            $company->setData('is_catalog_mvp_enabled', $data['is_catalog_mvp_enabled']);
        }

        $isEproUploadToQuoteToggle = $this->toggleConfig->getToggleConfigValue('explorers_epro_upload_to_quote');
        if ($isEproUploadToQuoteToggle) {
            $company->setData('is_epro_u2q_enabled', $data['is_epro_u2q_enabled']);
        }
            $company->setData('is_sensitive_data_enabled', $data['is_sensitive_data_enabled']);
            $company->setData('company_url_extention', strtolower(trim($data['company_url_extention'])));
            if (isset($data['image_field'][0]['url'])) {
                $parseUrl = parse_url($data['image_field'][0]['url']);
                $data['image_field'][0]['url'] = $parseUrl['path'];
                $company->setData('company_logo', json_encode($data['image_field'][0]));
            } else {
                $company->setData('company_logo', null);
            }
        $deliveryOptions = $data[self::ALLOWED_DELIVERY_OPTIONS] ?? [];
        $company->setData(self::ALLOWED_DELIVERY_OPTIONS, $this->json->serialize($deliveryOptions));

        //B-1421805 | Reduce compexity for Sonarlint
        $this->saveRecomendedLocation($data, $company);

        $paymentData = $this->request->getParam('company_payment_methods');
        if ($paymentData) {
            $shippingAccountNumber = $paymentData['fxo_shipping_account_number'] ?? '';
            $discountAccountNumber = $paymentData['fxo_discount_account_number'] ?? '';
            $fedexAccountNumber = $paymentData['fxo_account_number'] ?? '';
            $fedexAccountNumberEditable = $paymentData['fxo_account_number_editable'] ?? '';
            $shippingAccountNumberEditable = $paymentData['shipping_account_number_editable'] ?? '';
            $discountAccountNumberEditable = $paymentData['discount_account_number_editable'] ?? '';

            $company->setData('shipping_account_number', $shippingAccountNumber);
            $company->setData('discount_account_number', $discountAccountNumber);
            $company->setData('fedex_account_number', $fedexAccountNumber);
            $company->setData('fxo_account_number_editable', $fedexAccountNumberEditable);
            $company->setData('shipping_account_number_editable', $shippingAccountNumberEditable);
            $company->setData('discount_account_number_editable', $discountAccountNumberEditable);
        }

        if ($commercialStorefrontData = $this->request->getParam("authentication_rule")) {
            $this->loginCommercialStorefrontCheck($commercialStorefrontData, $company);
        }

        $company->setData('order_complete_confirm', $data['order_complete_confirm']);
        $company->setData('shipnotf_delivery', $data['shipnotf_delivery']);
        $company->setData('order_cancel_customer', $data['order_cancel_customer']);
        $company->setData(
            self::CUSTOM_BILLING_INVOICED,
            $this->request->getParam(self::CUSTOM_BILLING_INVOICED)
        );
        $company->setData(
            self::CUSTOM_BILLING_CREDIT_CARD,
            $this->request->getParam(self::CUSTOM_BILLING_CREDIT_CARD)
        );
        $company->setData(
            self::CUSTOM_BILLING_SHIPPING,
            $this->request->getParam(self::CUSTOM_BILLING_SHIPPING)
        );

        $companySelfRegData = $this->request->getParam('self_reg_login');
        $authRuleData = $this->request->getParam('authentication_rule');
        if ($companySelfRegData || $authRuleData) {
            $this->saveCompanySelfRegData($companySelfRegData, $authRuleData, null, $company);
        }

        $this->companyRepository->save($company);
        $id = $company->getId();

        //B-1421805 | Reduce compexity for Sonarlint
        if ($commercialStorefrontData['storefront_login_method'] == 'commercial_store_epro') {
            $this->prepareAndSaveCompanyRules($data, $id);
        }

        return $company;
    }

    /**
     * Save company restricted and recommended location information
     *
     * @param array $data
     * @param CompanyInterface
     * @return void
     */
    public function saveRecomendedLocation($data, $company)
    {
        /**
        Added Recommended Location Settings
        @Author: Anuj Kumar 18 Oct 2021
         **/

        // E-394577 : Restricted & Recommended Production Locations Toggle
        $isRestrictedRecommendedToggle = $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION);
        if (isset($data['allow_production_location']) && !empty($data['allow_production_location']) && $data['allow_production_location'] &&
        !$isRestrictedRecommendedToggle) {
            $company->setData('allow_production_location', $data['allow_production_location']);
            if ($data['production_location_option'] =='recommended_location_all_locations') {
                if ($data['is_restricted'] > 0) {
                    $company->setData('production_location_option', $data['production_location_option']);
                } else {
                    $this->messageManager->addNoticeMessage(__('Please select the Restricted Stores.'));
                    $company->setData('production_location_option', null);
                    $company->setData('allow_production_location', 0);
                }
            } else {
                $company->setData('production_location_option', $data['production_location_option']);
            }
        } elseif ($isRestrictedRecommendedToggle && isset($data['production_location_option']) && !empty($data['production_location_option'])) {
            $company->setData('production_location_option', $data['production_location_option']);
            $company->setData('allow_production_location', $data['allow_production_location']);
        } else {
            $company->setData('production_location_option', null);
            $company->setData('allow_production_location', 0);
        }
    }

    /**
     * prepare and save company rules
     *
     * @param array $data
     * @param int $id
     * @return void
     */
    public function prepareAndSaveCompanyRules($data, $id)
    {
        if (isset($data['rule_load'])) {
            $collection = $this->ruleFactory->create()->getCollection()
                ->addFieldToSelect('*')->addFieldToFilter('company_id', ['eq' => $id]);
            foreach ($collection as $item) {
                $item->delete();
            }
        }
        if ($data['acceptance_option'] == 'both') {
            $this->prepareCompanyRulesForBothAcceptanceOption($data, $id);
        } else {
            if ($data['acceptance_option'] == 'contact' && isset($data['rule_code_c']) && count($data['rule_code_c'])) {
                $this->saveRules('contact', $id, $data['rule_code_c']);
            }
            if (
                $data['acceptance_option'] == 'extrinsic'
                && isset($data['rule_code_e']) && count($data['rule_code_e'])
            ) {
                $this->saveRules('extrinsic', $id, $data['rule_code_e']);
            }
        }
    }

    /**
     * prepare and save company rules
     *
     * @return void
     */
    public function prepareCompanyRulesForBothAcceptanceOption($data, $id)
    {
        if (isset($data['rule_code_c']) && count($data['rule_code_c'])) {
            $this->saveRules('contact', $id, $data['rule_code_c']);
        }
        if (isset($data['rule_code_e']) && count($data['rule_code_e'])) {
            $this->saveRules('extrinsic', $id, $data['rule_code_e']);
        }
    }

    /**fcl_user_email_verification_user_display_message
     * Save company addtional data
     *
     * @return void
     */
    public function saveCompanySelfRegData($companySelfRegData, $authRuleData, $companyId, $company = null)
    {
        $companySelfRegData['self_reg_login_method'] = isset($authRuleData['self_reg_login_method'])
            ? $authRuleData['self_reg_login_method'] : '';
        $companySelfRegData['domains'] = isset($authRuleData['domains']) ? $authRuleData['domains'] : '';
        $companySelfRegData['error_message'] = isset($authRuleData['error_message']) ? $authRuleData['error_message']
            : '';
        $companySelfRegData['fcl_user_email_verification_error_message'] = isset($authRuleData['fcl_user_email_verification_error_message']) ? $authRuleData['fcl_user_email_verification_error_message'] : '';
        $companySelfRegData['fcl_user_email_verification_user_display_message'] = isset($authRuleData['fcl_user_email_verification_user_display_message']) ? $authRuleData['fcl_user_email_verification_user_display_message'] : '';
        if (
            isset($companySelfRegData['self_reg_login_method'])
            && $companySelfRegData['self_reg_login_method'] == 'registered_user'
        ) {
            $companySelfRegData['domains'] = '';
            $companySelfRegData['error_message'] = '';
        }

        if (
            isset($companySelfRegData['self_reg_login_method'])
            && $companySelfRegData['self_reg_login_method'] == 'admin_approval'
        ) {
            $companySelfRegData['domains'] = '';
        }

        $isNotEpro = isset($authRuleData['storefront_login_method'])
            && $authRuleData['storefront_login_method'] != 'commercial_store_epro';

        $generalData = $this->request->getParam('general');
        $isNotSDE = isset($generalData['is_sensitive_data_enabled'])
            && $generalData['is_sensitive_data_enabled'] != '1';

        $companySelfRegData['enable_selfreg'] = (int)($isNotSDE);

        if (isset($companySelfRegData['enable_selfreg'])) {
            if ($company) {
                $jsonSerializedData = $this->json->serialize($companySelfRegData);
                $company->setData('self_reg_data', $jsonSerializedData);
            } else {
                $collection = $this->companySelfRegDataFactory->create()
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $companyId])->getFirstItem();

                if (!$collection->getId()) {
                    $companySelfRegDataFactoryObj = $this->companySelfRegDataFactory->create();
                    $companySelfRegDataFactoryObj->setCompanyId($companyId);
                    $jsonSerializedData = $this->json->serialize($companySelfRegData);
                    $companySelfRegDataFactoryObj->setSelfRegData($jsonSerializedData);
                    $companySelfRegDataFactoryObj->save();
                } else {
                    $jsonSerializedData = $this->json->serialize($companySelfRegData);
                    $collection->setSelfRegData($jsonSerializedData);
                    $collection->save();
                }
            }
        }
    }

    /**
     * Save company addtional data
     *
     * @return void
     */
    public function loginCommercialStorefrontCheck($loginCommercialStorefront, CompanyInterface $company)
    {
        $company->setData('storefront_login_method_option', $loginCommercialStorefront['storefront_login_method']);
        if ($loginCommercialStorefront['storefront_login_method'] == 'commercial_store_sso' ||
            $loginCommercialStorefront['storefront_login_method'] == ExportCompanyData::SSO_WITH_FCL_LOGIN_METHOD) {
            $company->setData('sso_login_url', $loginCommercialStorefront['sso_login_url']);
            $company->setData('sso_logout_url', $loginCommercialStorefront['sso_logout_url']);
            $company->setData('sso_idp', $loginCommercialStorefront['sso_idp']);
            if ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')) {
                $company->setData('sso_group', $loginCommercialStorefront['sso_group']);
            }
        }
    }

    /**
     * Save company addtional data
     * B-1205796 : API integration for CC details and Billing details in Magento Admin
     *
     * @return void
     */
    public function saveAdditionalData($companyId)
    {
        $additionalData = $this->request->getParam('general');
        $ccToken = $additionalData['cc_token'] ?? null;
        $ccData = $additionalData['cc_data'] ?? null;
        $storeViewId = $additionalData['store_view_id'] ?? null;
        $storeId = $additionalData['store_id'] ?? null;
        $newStoreViewId = $additionalData['new_store_view_id'] ?? null;
        $newStoreId = $additionalData['new_store_id'] ?? null;
        // B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
        $ccTokenexpiryDateTime = $additionalData['cc_token_expiry_date_time'] ?? null;

        $isPromoDiscountEnable = false;
        $isAccountDiscountEnable = false;
        $productionLocationTabData = $this->request->getParam('production_location');
        $notificationBannerConfig = $this->request->getParam('notification_banner_config');
        $isPromoDiscountEnable = $productionLocationTabData[self::IS_PROMO_DISCOUNT_ENABLED] ?? false;
        $isAccountDiscountEnable = $productionLocationTabData[self::IS_ACCOUNT_DISCOUNT_ENABLED] ?? false;
        $eproNewPlatformOrderCreation = $productionLocationTabData[self::EPRO_NEW_PLATFORM_ORDER_CREATION] ?? false;
        $isApprovalWorkflowEnabled = $productionLocationTabData[self::IS_B2B_ORDER_APPROVAL_ENABLED] ?? false;
        // B-2011063 B2B order aproval workflow company_payment_options should be fedex account number
        if ($this->adminConfigHelper->isOrderApprovalB2bGloballyEnabled()) {
            $paymentData = $this->request->getParam('company_payment_methods');
            $companyPaymentOtions = $paymentData['company_payment_options'] ?? [];
            if (in_array('creditcard',$companyPaymentOtions) && $isApprovalWorkflowEnabled) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You must check fedex account number and uncheck ' .
                        'credit card from Applicable Payment Methods from payment methods setting ' .
                        'to enable B2b Order Approval Workflow')
                );
            }
        }
        $isReorderEnable = false;
        $isTermsAndConditions = false;
        $allPrintProductsCmsBlockIdentifier = false;
        if ($this->toggleConfig->getToggleConfigValue('explorers_d195445_fix')) {
            $isTermsAndConditions = $productionLocationTabData[self::TERMS_AND_CONDITIONS] ?? false;
        }
        $reorderConfigurationData = $this->request->getParam('catalog_document');
        $uiData = $this->request->getParam('company_logo');
        $isReorderEnable = $reorderConfigurationData[self::IS_REORDER_ENABLED] ?? false;
        $homepageCmsBlockIdentifier = $uiData[self::HOMEPAGE_CMS_BLOCK_IDENTIFIER]??'';
        $allPrintProductsCmsBlockIdentifier =
            $reorderConfigurationData[self::ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER] ?? '';

        $orderNotes = $this->getOrderNotesFormData();

        $ccExpiryTime = null;
        if ($ccTokenexpiryDateTime != null) {
            $ccExpiryTime = $this->timezone->date($ccTokenexpiryDateTime)->format(DateTime::DATETIME_PHP_FORMAT);
        }
        $collection = $this->additionalDataFactory->create()
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter(AdditionalData::COMPANY_ID, ['eq' => $companyId]);
        if (!$collection->getSize()) {
            $saveCompany = false;
            $additionalDataFactoryObj = $this->additionalDataFactory->create();

            if ($isPromoDiscountEnable || $isAccountDiscountEnable) {
                $additionalDataFactoryObj->setIsPromoDiscountEnabled($isPromoDiscountEnable);
                $additionalDataFactoryObj->setIsAccountDiscountEnabled($isAccountDiscountEnable);
                $saveCompany = true;
            }

            if ($eproNewPlatformOrderCreation) {
                $additionalDataFactoryObj->setEproNewPlatformOrderCreation($eproNewPlatformOrderCreation);
                $saveCompany = true;
            }
            if ($isApprovalWorkflowEnabled) {
                $additionalDataFactoryObj->setIsApprovalWorkflowEnabled($isApprovalWorkflowEnabled);
                $saveCompany = true;
            }
            if ($isReorderEnable) {
                $additionalDataFactoryObj->setIsReorderEnabled($isReorderEnable);
                $saveCompany = true;
            }

            if ($allPrintProductsCmsBlockIdentifier) {
                $additionalDataFactoryObj->setAllPrintProductsCmsBlockIdentifier($allPrintProductsCmsBlockIdentifier);
                $saveCompany = true;
            }
            if ($homepageCmsBlockIdentifier) {
                $additionalDataFactoryObj->setHomepageCmsBlockIdentifier($homepageCmsBlockIdentifier);
                $saveCompany = true;
            }

            $saveCompany = $this->saveOrderNotes($orderNotes, $additionalDataFactoryObj);

            if ($isTermsAndConditions) {
                $additionalDataFactoryObj->setTermsAndConditions($isTermsAndConditions);
                $saveCompany = true;
            }

            if ($storeViewId && $storeId) {
                $additionalDataFactoryObj->setStoreId($storeId);
                $additionalDataFactoryObj->setStoreViewId($storeViewId);
                $saveCompany = true;
            }
            if ($newStoreViewId && $newStoreId) {
                $additionalDataFactoryObj->setNewStoreId($newStoreId);
                $additionalDataFactoryObj->setNewStoreViewId($newStoreViewId);
                $saveCompany = true;
            }
            if ($ccToken && $ccData && $ccExpiryTime) {
                $additionalDataFactoryObj->setCcToken($ccToken);
                $additionalDataFactoryObj->setCcData($ccData);
                // B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
                $additionalDataFactoryObj->setCcTokenExpiryDateTime($ccExpiryTime);
                $saveCompany = true;
            }
            $savedStatus = $this->saveCompanyPaymentData($additionalDataFactoryObj);
            if ($savedStatus) {
                $saveCompany = true;
            }

            if ($saveCompany) {
                $this->saveNotificationBannerData($notificationBannerConfig, $additionalDataFactoryObj);
                $additionalDataFactoryObj->setCompanyId($companyId);
                $additionalDataFactoryObj->save();
            }
        } else {
            foreach ($collection as $item) {
                $item->setStoreId($storeId);
                $item->setStoreViewId($storeViewId);
                $item->setNewStoreId($newStoreId);
                $item->setNewStoreViewId($newStoreViewId);
                $item->setCcToken($ccToken);
                $item->setCcData($ccData);
                $this->saveCompanyPaymentData($item);
                $item->setCcTokenExpiryDateTime($ccExpiryTime);
                $item->setIsPromoDiscountEnabled($isPromoDiscountEnable);
                $item->setIsAccountDiscountEnabled($isAccountDiscountEnable);
                $item->setEproNewPlatformOrderCreation($eproNewPlatformOrderCreation);
                $item->setIsApprovalWorkflowEnabled($isApprovalWorkflowEnabled);
                $this->saveOrderNotes($orderNotes, false, $item);
                $item->setIsReorderEnabled($isReorderEnable);
                $item->setTermsAndConditions($isTermsAndConditions);
                $this->saveNotificationBannerData($notificationBannerConfig, $item);
                $item->setAllPrintProductsCmsBlockIdentifier($allPrintProductsCmsBlockIdentifier);
                $item->setHomepageCmsBlockIdentifier($homepageCmsBlockIdentifier);
                $item->save();
            }
        }
    }

    /**
     * Save notification banner data
     *
     * @param array $notificationBannerConfig
     * @param object $setDataObject
     * @return void
     */
    public function saveNotificationBannerData($notificationBannerConfig, $setDataObject)
    {
        $isBannerEnabled = $notificationBannerConfig[self::IS_BANNER_ENABLE] ?? 0;
        $bannerTitle = $notificationBannerConfig[self::BANNER_TITLE] ?? '';
        $bannerIconography = $notificationBannerConfig[self::ICONOGRAPHY] ?? '';
        $bannerNotificationDescription = $notificationBannerConfig[self::BANNER_DESCRIPTION] ?? '';
        $bannerCtaText = $notificationBannerConfig[self::BANNER_CTA_TEXT] ?? '';
        $bannerCtaLink = $notificationBannerConfig[self::BANNER_CTA_LINK] ?? '';
        $isLinkOpenNewTab = $notificationBannerConfig[self::BANNER_LINK_OPEN_IN_NEW_TAB] ?? 0;

        $setDataObject->setIsBannerEnable($isBannerEnabled);
        $setDataObject->setBannerTitle($bannerTitle);
        $setDataObject->setIconography($bannerIconography);
        $setDataObject->setDescription($bannerNotificationDescription);
        $setDataObject->setCtaText($bannerCtaText);
        $setDataObject->setCtaLink($bannerCtaLink);
        $setDataObject->setLinkOpenInNewTab($isLinkOpenNewTab);
    }

    /**
     * Save company payment configuration
     *
     * AdditionalData $item
     * @return bool
     */
    public function saveCompanyPaymentData($item)
    {
        $paymentData = $this->request->getParam('company_payment_methods');
        $paymentOption = $paymentData['company_payment_options'] ?? [];
        $creditCardOptions = $paymentData['creditcard_options'] ?? '';
        $fedexAccountOptions = $paymentData['fedex_account_options'] ?? '';
        $defaultPaymentMethod = $paymentData['default_payment_method'] ?? '';

        if ($paymentOption) {
            $item->setCompanyPaymentOptions($this->json->serialize($paymentOption));
            $item->setCreditcardOptions($creditCardOptions);
            $item->setFedexAccountOptions($fedexAccountOptions);
            if (count($paymentOption) > 1 && $defaultPaymentMethod) {
                $item->setDefaultPaymentMethod($defaultPaymentMethod);
            } else {
                $item->setDefaultPaymentMethod(current($paymentOption));
            }
            return true;
        }

        return false;
    }

    /**
     * @Athor Pratibha
     * Save Company Rules
     *
     * @param string $key
     * @param int $company_id
     * @param array rules
     * @return boolean
     */
    public function saveRules($key, $companyId, $rules)
    {
        foreach ($rules as $rule) {
            if (!empty(trim($rule))) {
                $ruleModel = $this->ruleFactory->create();
                $ruleModel->setData(['company_id' => $companyId, 'rule_code' => $rule, 'type' => $key]);
                $ruleModel->save();
            }
        }
        return true;
    }

    /**
     * Filter customer-related data from request
     *
     * @return array
     */
    private function extractCustomerData(): array
    {
        $data = [];
        $requestParams = $this->request->getParams();
        if (
            is_array($requestParams)
            && !empty($requestParams['company_admin'])
            && is_array($requestParams['company_admin'])
        ) {
            $data = $requestParams['company_admin'];
        }

        return $data;
    }

    /**
     * Populate company object with request data.
     *
     * @param CompanyInterface $company
     * @param array $data
     * @return CompanyInterface
     */
    public function setCompanyRequestData(CompanyInterface $company, array $data)
    {
        $this->dataObjectHelper->populateWithArray(
            $company,
            $data,
            CompanyInterface::class
        );

        return $company;
    }
    /**
     * @Athor Pratibha
     * Validate min & max extrinsic rule
     *
     * @param array $authData
     * @return array
     */
    public function validateMinMaxRule($authData)
    {
        $resultData = [];
        $responseData = '';
        if (isset($authData['rule_code_e'])) {
            $resultData = array_values(array_filter($authData['rule_code_e']));
        }
        if ($authData['hidden_auth_flag'] && !(isset($authData['rule_load'])) && !is_array($responseData)) {
            $responseData = ['error' => 0, 'msg' => ''];
        }
        if (($authData['acceptance_option'] == 'both' || $authData['acceptance_option'] == 'extrinsic')
            && (count($resultData) == 0) && !is_array($responseData)
        ) {
            $responseData =  ['error' => 1, 'msg' => 'Min. one extrinsic rule must be defined'];
        }
        if (($authData['acceptance_option'] == 'both' || $authData['acceptance_option'] == 'extrinsic')
            && (count($resultData) > 3) && !is_array($responseData)
        ) {
            $responseData =  ['error' => 1, 'msg' => 'Max. 3 extrinsic rule can be defined'];
        }

        return is_array($responseData) ?  $responseData : ['error' => 0, 'msg' => ''];
    }

    /**
     * Get form order notes data
     *
     * @return string
     */
    public function getOrderNotesFormData()
    {
        $orderNotes = $this->request->getParam('production_location')[self::ORDER_NOTES] ?? '';

        return $orderNotes;
    }

    /**
     * Save order notes
     *
     * @param string $orderNotes
     * @param object|bool $additionalDataFactoryObj
     * @param object $item
     * @return bool|void
     */
    public function saveOrderNotes($orderNotes, $additionalDataFactoryObj = false, $item = false)
    {
        if ($additionalDataFactoryObj) {
            $additionalDataFactoryObj->setOrderNotes($orderNotes);
            return true;
        } elseif ($item) {
            $item->setOrderNotes($orderNotes);
        } else {
            return false;
        }
    }

    /**
     * Validate user settings
     *
     * @return array
     */
    public function validateUserSettings($authData)
    {
        if ($authData['allow_own_document'] == 0 && $authData['allow_shared_catalog'] == 0) {
            return [
                'error' => 1,
                'msg' => 'Catalog & Document User settings -> atleast
                     one should be selected  in Catalog & Document User Settings '
            ];
        }

        return ['error' => 0, 'msg' => ''];
    }

    /**
     * Validate and save BCC Emails
     *
     * @return void
     */
    public function validateAndSaveBccEmail($company, $emailData)
    {
        if ($emailData != "") {
            $emailData = preg_replace('/\s+/', '', $emailData);
            $emails = explode(',', $emailData);
            $counter = 0;
            foreach($emails as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->messageManager->
                    addErrorMessage(__('Please enter valid email addresses for BCC (Ex: johndoe@domain.com,...).'));
                    array_splice($emails, $counter, 1);
                    $counter--;
                }
                $counter++;
            }
            $emailData = implode(', ', $emails);
            $company->setData('bcc_comma_seperated_email', $emailData);
        } else {
            $company->setData('bcc_comma_seperated_email', $emailData);
        }
    }

    /**
     * @param $id
     * @param $requestData
     * @return bool
     */
    protected function checkRequestDataCreateAllNewEntities($id, $requestData) {
        return is_null($id)
            && ($requestData['customer_group_id'] ?? false) == self::CREATE_NEW_VALUE
            && ($requestData['shared_catalog_id'] ?? false) == self::CREATE_NEW_VALUE;
    }

    /**
     * Return true if Root Category was selected as Create New and Customer Group was selected from existing list
     *
     * @param $id
     * @param $requestData
     * @return bool
     */
    protected function isOnlyCreateRootCategory($id, $requestData) {
        $customerGroupId = ($requestData['customer_group_id'] ?? false) == self::CREATE_NEW_VALUE;
        $sharedCatalogId = ($requestData['shared_catalog_id'] ?? false) == self::CREATE_NEW_VALUE;
        return is_null($id)
            && $sharedCatalogId && !$customerGroupId;
    }

    /**
     * Return true if Root Category was selected from existing list and Customer Group was selected as Create New
     *
     * @param $id
     * @param $requestData
     * @return bool
     */
    protected function isOnlyCreateCustomerGroup($id, $requestData) {
        $customerGroupId = ($requestData['customer_group_id'] ?? false) == self::CREATE_NEW_VALUE;
        $sharedCatalogId = ($requestData['shared_catalog_id'] ?? false) == self::CREATE_NEW_VALUE;
        return is_null($id)
            && !$sharedCatalogId && $customerGroupId;
    }

    /**
     * Validate and save Non-Standard Catalog Distribution List
     *
     * @param CompanyInterface $company
     * @param string $emailData
     * @return void
     */
    public function validateAndSaveNonStandardCatalogDistributionList(CompanyInterface $company, $emailData)
    {
        if ($emailData != "") {
            $emailData = preg_replace('/\s+/', '', $emailData);
            $emails = explode(',', $emailData);
            $counter = 0;
            foreach ($emails as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->messageManager->
                    addErrorMessage(__('Please enter valid email addresses
                    for non-standard catalog distribution list (Ex: johndoe@domain.com,...).'));
                    array_splice($emails, $counter, 1);
                    $counter--;
                }
                $counter++;
            }
            $emailData = implode(', ', $emails);
            $company->setData('non_standard_catalog_distribution_list', $emailData);
        } else {
            $company->setData('non_standard_catalog_distribution_list', $emailData);
        }
    }

    /**
     * @return true
     */
    public function homepageCMSValidation($data){
        try{
            $homepageCMSBlockIdentifier = $data['homepage_cms_block_identifier']??'';
            if($homepageCMSBlockIdentifier && $homepageCMSBlockIdentifier!=''){
                $block = $this->blockFactory->create();
                $blockItem = $block->load($homepageCMSBlockIdentifier);
                if(!$blockItem->getId()){
                    $this->messageManager->
                    addErrorMessage(__('Please enter valid CMS identifier, we\'re unable to find CMS Block.'.$homepageCMSBlockIdentifier ));
                    return true;
                }
            }
        }catch(\Exception $e){
            $this->messageManager->
            addErrorMessage(__($e->getMessage()));
        }
        return false;
    }
}
