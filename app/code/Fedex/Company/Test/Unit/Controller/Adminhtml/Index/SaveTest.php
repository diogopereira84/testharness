<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Index;

use Fedex\Company\Controller\Adminhtml\Index\Save;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AuthDynamicRows;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection as RowsCollection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Model\CompanySelfRegData;
use Fedex\SelfReg\Model\CompanySelfRegDataFactory;
use Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData\Collection as CompanySelfRegDataCollection;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\CompanySuperUserGet;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Fedex\Company\Helper\Data;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\Shipto\Controller\Adminhtml\Plocation\Save as RestrictLocationSave;
use Magento\Customer\Model\Session as customerSession;




use Psr\Log\LoggerInterface;

/**
 * Unit tests for adminhtml company save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var (\Magento\Backend\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $session;
    protected $messageManager;
    protected $resultRedirectFactory;
    protected $toggleConfigMock;
    /**
     * @var (\Fedex\SDE\Helper\SdeHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sdeHelper;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $companyHelper;
    protected $additionalDataCollectionMock;
    protected $companySelfRegDataFactoryMock;
    protected $companySelfRegDataMock;
    protected $companySelfRegDataCollectionMock;
    protected $loggerMock;
    /**
     * @var (\Fedex\Company\Api\Data\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterfaceMock;
    protected $saveLocationMock;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSessionMock;
    /**
     * @var (\Magento\Cms\Model\BlockFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $blockFactoryMock;
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
    const RECIPIENT_ADDRESS_FROM_PO = 'recipient_address_from_po';
    const RULE_LOAD = 'rule_load';
    const SITE_NAME = 'site_name';
    const IS_QUOTE_REQUEST = "is_quote_request";
    const IS_EXPIRING_ORDER = "is_expiring_order";
    const IS_EXPIRED_ORDER = "is_expired_order";
    const IS_ORDER_REJECT = "is_order_reject";
    const ALLOW_OWN_DOCUMENT = "allow_own_document";
    const ALLOW_SHARED_CATALOG = "allow_shared_catalog";
    const PAYMENT_OPTION = 'payment_option';
    const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    const DISCOUNT_ACCOUNT_NUMBER = 'discount_account_number';
    const SHIPPING_ACCOUNT_NUMBER = 'shipping_account_number';
    const ORDER_COMPLETE_CONFIRM = 'order_complete_confirm';
    const SHIPNOTF_DELIVERY = 'shipnotf_delivery';
    const ORDER_CANCEL_CUSTOMER = 'order_cancel_customer';
    const ENABLE_UPLOAD_SECTION = 'enable_upload_section';
    const ENABLE_CATALOG_SECTION = 'enable_catalog_section';
    const ALLOWED_DELIVERY_OPTIONS = 'allowed_delivery_options';

    const IMAGE_FIELD = 'image_field';
    const IS_SENSETIVE_DATA_ENABLED = 'is_sensitive_data_enabled';
    const COMPANY_URL_EXTENTION = 'company_url_extention';
    const ORDER_NOTES = 'order_notes';
    const IS_REORDER_ENABLED ='is_reorder_enabled';
    const ORDER_CONFIRMATION_EMAIL = "is_success_email_enable";

    const NOTIFICATION_PARAM_DATA = [
        Save::IS_BANNER_ENABLE => 1,
        Save::BANNER_TITLE => 1,
        Save::ICONOGRAPHY => 'warning' ,
        Save::BANNER_DESCRIPTION => 'dfs',
        Save::BANNER_CTA_TEXT => 1,
        Save::BANNER_CTA_LINK => 'ddsfs',
        Save::BANNER_LINK_OPEN_IN_NEW_TAB => 1
    ];

    protected $authDynamicRowsMock;
    protected $rowsCollectionMock;
    protected $dataObjectProcessorMock;
    protected $companySuperUserGetMock;
    protected $companyRepositoryInterfaceMock;
    protected $companyInterfaceFactoryMock;
    protected $authDynamicRowsFactoryMock;
    protected $resourceConnectionMock;
    protected $dataObjectHelperMock;
    protected $objectManager;
    protected $saveMock;
    protected $requestMock;
    protected $connectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    protected $companyInterfaceMock;
    protected $objectManagerInstance;
    private $eventManager;

    protected $timeZoneMock;

   /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelperMock;

    protected function setUp(): void
    {
        $this->authDynamicRowsMock = $this->createMock(AuthDynamicRows::class);

        $this->dataObjectProcessorMock = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyData'])
            ->getMock();

        $this->companySuperUserGetMock = $this->getMockBuilder(CompanySuperUserGet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass(); //B-1326233

        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();

        $this->companyInterfaceFactoryMock = $this->getMockBuilder(CompanyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMockForAbstractClass();

        $this->authDynamicRowsFactoryMock = $this->createPartialMock(AuthDynamicRowsFactory::class, ['create']);
        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectHelperMock = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultRedirectFactory = $this->getMockBuilder(BackendRedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->timeZoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCollection',
                    'addFieldToSelect',
                    'addFieldToFilter',
                    'getSize',
                    'setStoreViewId',
                    'setStoreId',
                    'setCcToken',
                    'setCcData',
                    'save',
                    'getIterator',
                    'setOrderNotes',
                    'setTermsAndConditions'
                ]
            )
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateNewtworkId', 'validateCompanyName', 'isCompanyUrlExtentionDuplicate'])
            ->getMock();

        $this->additionalDataCollectionMock = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFieldToSelect',
                    'addFieldToFilter',
                    'getIterator',
                    'setStoreId',
                    'setStoreViewId',
                    'setCcToken',
                    'setCcData',
                    'getSize',
                    'getOrderNotes'
                ]
            )
            ->getMock();

        $this->companySelfRegDataFactoryMock = $this->getMockBuilder(CompanySelfRegDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companySelfRegDataMock = $this->getMockBuilder(CompanySelfRegData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getCollection',
                'addFieldToSelect',
                'addFieldToFilter',
                'getSize',
                'setSelfRegData',
                'setCompanyId',
                'save',
                'delete',
                'getIterator'
            ])
            ->getMock();

        $this->companySelfRegDataCollectionMock = $this->getMockBuilder(CompanySelfRegDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToSelect',
                'addFieldToFilter',
                'getIterator',
                'setSelfRegData',
                'setCompanyId',
                'getFirstItem'
            ])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isOrderApprovalB2bGloballyEnabled'])
            ->getMock();
        $this->saveLocationMock = $this->getMockBuilder(RestrictLocationSave::class)
            ->disableOriginalConstructor()
           ->setMethods(['saveLocation'])
           ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
           ->disableOriginalConstructor()
          ->getMock();

        $this->blockFactoryMock = $this->getMockBuilder(BlockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->saveMock = $this->objectManager->getObject(
            Save::class,
            [
                'dataObjectProcessor' => $this->dataObjectProcessorMock,
                'superUser' => $this->companySuperUserGetMock,
                'companyRepository' => $this->companyRepositoryInterfaceMock,
                'companyDataFactory' => $this->companyInterfaceFactoryMock,
                'ruleFactory' => $this->authDynamicRowsFactoryMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'dataObjectHelper' => $this->dataObjectHelperMock,
                'request' => $this->requestMock,
                '_eventManager' => $this->eventManager,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                '_session' => $this->session,
                'toggleConfig' => $this->toggleConfigMock,
                'sdeHelper' => $this->sdeHelper,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'companySelfRegDataFactory' => $this->companySelfRegDataFactoryMock,
                'logger' => $this->loggerMock,
                'timezone' => $this->timeZoneMock,
                'companyHelper' => $this->companyHelper,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'restrictLocationSave'=>$this->saveLocationMock,
                'customerSession'=>$this->customerSessionMock
            ]
        );
    }
    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $companyId = 1;
        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::REGION_ID => 2,
            CompanyInterface::COUNTRY_ID => 'US',
            CompanyInterface::REGION => 'Alabama',
            'network_id' => 'Network1',
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'])
            ->willReturnOnConsecutiveCalls($companyId, $params);

        $this->messageManager->expects($this->any())
            ->method('addError')
            ->with('Network1 Newtwork id already assigned to some other company.')
            ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();


        $locationparams['general']=['new_compnay_location_id'=>1234];
        $locationparams['production_location']=['production_location_option'=>'recommended_stores_all_location'];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($locationparams);
        $this->saveLocationMock->expects($this->any())->method('saveLocation')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithoutNetworkId()
    {
        $companyId = 1;
        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::REGION_ID => 2,
            CompanyInterface::COUNTRY_ID => 'US',
            CompanyInterface::REGION => 'Alabama',
            'network_id' => 'Network1',
            'storefront_login_method' => 'commercial_store_epro',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'profile_api_url' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_e' => [
                0 => 'Name',
                1 => 'Email',
                ],
        ];

        // B-1385081
        $this->requestMock->expects($this->any())->method('getParam')->willReturn($params);
        $this->companyHelper->expects($this->any())->method('validateNewtworkId')->willReturn(true);
        $this->companyHelper->expects($this->any())->method('validateCompanyName')->willReturn(true);
        $this->companyHelper->expects($this->any())->method('isCompanyUrlExtentionDuplicate')->willReturn(true);

        $this->messageManager->expects($this->any())
            ->method('addError')
            ->with('Example Company Name Company already assigned to some other company.')
            ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
        $this->assertEquals($result, $this->saveMock->execute());
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithoutCompany()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1, 'rule_load' => 'val'];
        $params = [

            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::REGION_ID => 2,
            CompanyInterface::COUNTRY_ID => 'US',
            CompanyInterface::REGION => 'Alabama',
            'network_id' => 'Network1',
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'])
            ->willReturnOnConsecutiveCalls($companyId, $params, $authData);

        $this->messageManager->expects($this->any())
            ->method('addError')
            ->with('Min. one extrinsic rule must be defined')
            ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithoutValidationRule()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 0];
        $params = [

            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::REGION_ID => 2,
            CompanyInterface::COUNTRY_ID => 'US',
            CompanyInterface::REGION => 'Alabama',
            'network_id' => 'Network1'
        ];

        // B-1385081
        $this->requestMock->method('getParam')
        ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
        ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithCompanyData()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'contact', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];
        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //Self::ACCEPTANCE_OPTION => 'both',
            self::RULE_CODE_E => '',
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->rowsCollectionMock =
            $this->createMock(RowsCollection::class);

        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);

        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->will($this->returnSelf());
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->rowsCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithToggleOn()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'contact', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];

        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            self::RULE_CODE_E => '',
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            'allow_production_location' => 1,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 1,
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);

        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);

        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->will($this->returnSelf());
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->rowsCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithOption()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'contact', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];

        $params = [

            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            self::RULE_CODE_E => '',
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            'allow_production_location' => 1,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 0,
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);

        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);

        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->will($this->returnSelf());
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->rowsCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'extrinsic', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];

        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //Self::ACCEPTANCE_OPTION => 'both',
            self::RULE_CODE_E => ['key' => 'value'],
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);

        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);

        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->will($this->returnSelf());
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->rowsCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->eventManager->expects($this->any())
        ->method('dispatch')
        ->with(
            'adminhtml_company_save_after', ['company' => $this->companyInterfaceMock, 'request' => $this->requestMock]
        )
        ->willThrowException($exception);

        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];

        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //Self::ACCEPTANCE_OPTION => 'both',
            self::RULE_CODE_E => ['key' => 'value'],
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '123',
            self::SHIPPING_ACCOUNT_NUMBER => '123',
            self::DISCOUNT_ACCOUNT_NUMBER => '123',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['id'], ['general'], ['authentication_rule'], ['catalog_document'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, $params, $authData, $userSetting);

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);

        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);

        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->will($this->returnSelf());
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->rowsCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $exception = new \Exception();

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with(
                'adminhtml_company_save_after', [
                    'company' => $this->companyInterfaceMock,
                    'request' => $this->requestMock
                ]
            )
            ->willThrowException($exception);

        $this->messageManager->expects($this->any())
        ->method('addError')
        ->with('Catalog & Document User settings -> atleast one should be selected in Catalog & Document User Settings')
        ->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $result->expects($this->any())->method('setPath')->with('company/index/new')->willReturnSelf();
    }

    /**
     * Test for validateMinMaxRule method.
     *
     * @return void
     */
    public function testValidateMinMaxRule()
    {
        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1, 'rule_code_e' => ['key' => 'value']];
        $this->assertEquals(['error' => 0, 'msg' => ''], $this->saveMock->validateMinMaxRule($authData));

        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1, 'rule_load' => 2];
        $this->assertEquals(
            ['error' => 1, 'msg' => 'Min. one extrinsic rule must be defined'],
            $this->saveMock->validateMinMaxRule($authData)
        );

        $authData = [
            'acceptance_option' => 'both',
            'hidden_auth_flag' => 1,
            'rule_load' => 2,
            'rule_code_e' => ['value', 'value1', 'value2', 'valu4']
        ];

        $this->assertEquals(
            ['error' => 1, 'msg' => 'Max. 3 extrinsic rule can be defined'],
            $this->saveMock->validateMinMaxRule($authData)
        );

        $authData = [
            'acceptance_option' => 'both',
            'hidden_auth_flag' => 1,
            'rule_load' => 2,
            'rule_code_e' => ['key' => 'value', 'key1' => 'value1', 'key2' => 'value2']
        ];
        $this->assertEquals(['error' => 0, 'msg' => ''], $this->saveMock->validateMinMaxRule($authData));
    }

    /**
     * Test for saveRules method.
     *
     * @return void
     */
    public function testSaveRules()
    {
        $companyid = 1;
        $key = 'both';
        $rules = ['email' => 'vivek2.singh@infogain.com', 'firstname' => 'vicky', 'lastname' => 'User'];

        $this->authDynamicRowsMock = $this->getMockBuilder(\Fedex\Company\Model\AuthDynamicRows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);
        $this->authDynamicRowsMock->expects($this->any())->method('setData');
        $this->authDynamicRowsMock->expects($this->any())->method('save');
        $this->returnSelf($this->saveMock->saveRules($key, $companyid, $rules));
    }

    /**
     * @test testSaveAdditionalDataWithDataAlreadyExists
     */
    public function testSaveAdditionalDataWithDataAlreadyExists()
    {
        $companyId = 31;
        $params = [
            AdditionalData::STORE_ID => 44,
            AdditionalData::STORE_VIEW_ID => 66,
            'cc_token_expiry_date_time' => "2027-01-01 01:01:01"
        ];

        $paramsData = [
            Save::IS_PROMO_DISCOUNT_ENABLED => 1,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => 1,
            Save::ORDER_NOTES => 1,
            Save::IS_REORDER_ENABLED => 1,
            Save::TERMS_AND_CONDITIONS => 1
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['general'], ['production_location'],)
            ->willReturnOnConsecutiveCalls($params, $paramsData);

        $this->timeZoneMock->expects($this->exactly(1))->method('date')->willReturnSelf();
        $this->timeZoneMock->expects($this->exactly(1))->method('format')
            ->willReturn("2027-01-01 01:01:01");

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->additionalDataMock]));

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreViewId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveAdditionalData($companyId));
    }

    /**
     * @test testSaveAdditionalDataWithNullAlreadyExists
     */
    public function testSaveAdditionalDataWithNullAlreadyExists()
    {
        $companyId = 31;
        $params = [
            AdditionalData::STORE_ID => '',
            AdditionalData::STORE_VIEW_ID => '',
            'cc_token_expiry_date_time' => "2027-01-01 01:01:01"

        ];
        $paramsData = [
            Save::IS_PROMO_DISCOUNT_ENABLED => 1,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => 1,
            Save::ORDER_NOTES,
            Save::IS_REORDER_ENABLED =>1,
            Save::TERMS_AND_CONDITIONS => 1
        ];


        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['general'], ['production_location'], ['notification_banner_config'])
            ->willReturnOnConsecutiveCalls($params, $paramsData, self::NOTIFICATION_PARAM_DATA);

        $this->timeZoneMock->expects($this->exactly(1))->method('date')->willReturnSelf();
        $this->timeZoneMock->expects($this->exactly(1))->method('format')
            ->willReturn("2027-01-01 01:01:01");

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->additionalDataMock]));

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveAdditionalData($companyId));
    }

    /**
     * @test testSaveAdditionalDataWithNewData
     */
    public function testSaveAdditionalDataWithNewData()
    {
        $companyId = 31;
        $params = [
            AdditionalData::STORE_ID => 44,
            AdditionalData::STORE_VIEW_ID => 66,
            AdditionalData::CREDIT_CARD_TOKEN => 'abcdefghijkl',
            AdditionalData::CREDIT_CARD_DATA => '{
                "nameOnCard":"Customer Name",
                "ccNumber":"05100",
                "ccType":"MASTERCARD",
                "ccExpiryMonth":"03",
                "ccExpiryYear":"2026",
                "addressLine1":"Address1",
                "addressLine2":"Address2",
                "city":"City",
                "state":"FL",
                "country":"US",
                "zipCode":"52041"
            }',
            'cc_token_expiry_date_time' => "2027-01-01 01:01:01",
            'new_store_view_id' => 788,
            'new_store_id' => 78
        ];

        $paramsData = [
            Save::IS_PROMO_DISCOUNT_ENABLED => 1,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => 1,
            Save::ORDER_NOTES => 1,
            Save::IS_REORDER_ENABLED => 1,
            Save::TERMS_AND_CONDITIONS => 1
        ];


        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['general'], ['production_location'])
            ->willReturnOnConsecutiveCalls($params, $paramsData);

        $this->timeZoneMock->expects($this->exactly(1))->method('date')->willReturnSelf();
        $this->timeZoneMock->expects($this->exactly(1))->method('format')
            ->willReturn("2027-01-01 01:01:01");

        // B-1385081
        $this->additionalDataFactoryMock->method('create')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls($this->additionalDataMock, $this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getSize')
            ->willReturn(0);

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreViewId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setCcToken')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setCcData')
            ->willReturnSelf();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->additionalDataMock->expects($this->any())
            ->method('setOrderNotes')
            ->willReturnSelf('test');

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveAdditionalData($companyId));
    }

    /**
     * @test testSaveAdditionalDataWithNewData toggle of discount
     */
    public function testSaveAdditionalDataWithNewDataDiscountToggleOff()
    {
        $companyId = 31;
        $userSetting = ['is_reorder_enabled' => 1, 'allow_own_document' => 0, 'allow_shared_catalog' => 1];
        $params = [
            AdditionalData::STORE_ID => 44,
            AdditionalData::STORE_VIEW_ID => 66,
            AdditionalData::CREDIT_CARD_TOKEN => 'abcdefghijkl',
            AdditionalData::CREDIT_CARD_DATA => '{
                "nameOnCard":"Customer Name",
                "ccNumber":"05100",
                "ccType":"MASTERCARD",
                "ccExpiryMonth":"03",
                "ccExpiryYear":"2026",
                "addressLine1":"Address1",
                "addressLine2":"Address2",
                "city":"City",
                "state":"FL",
                "country":"US",
                "zipCode":"52041"
            }',
            'cc_token_expiry_date_time' => "2027-01-01 01:01:01",
            'new_store_view_id' => 788,
            'new_store_id' => 78
        ];

        $paramsData = [
            Save::IS_PROMO_DISCOUNT_ENABLED => 1,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => 1,
            Save::ORDER_NOTES => 1,
            Save::TERMS_AND_CONDITIONS => 1
        ];
        $this->adminConfigHelperMock->expects($this->once())->method('isOrderApprovalB2bGloballyEnabled')->willReturn(true);
        $defaultpaymentData = ['company_payment_options' => [
            'creditcard',
            'fedexaccountnumber'
        ]];

        // B-1385081
        $this->requestMock->method('getParam')->withConsecutive(
            ['general'],
            ['production_location'],
            ['notification_banner_config'],
            ['company_payment_methods'],
            ['catalog_document']
        )->willReturnOnConsecutiveCalls(
            $params,
            $paramsData,
            self::NOTIFICATION_PARAM_DATA,
            $defaultpaymentData,
            $userSetting
        );

        $this->timeZoneMock->expects($this->exactly(1))->method('date')->willReturnSelf();
        $this->timeZoneMock->expects($this->exactly(1))->method('format')
            ->willReturn("2027-01-01 01:01:01");

        // B-1385081
        $this->additionalDataFactoryMock->method('create')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls($this->additionalDataMock, $this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getSize')
            ->willReturn(0);

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreViewId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setCcToken')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setCcData')
            ->willReturnSelf();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->additionalDataMock->expects($this->any())
            ->method('setOrderNotes')
            ->willReturnSelf('test');

        $this->additionalDataMock->expects($this->any())
            ->method('setTermsAndConditions')
            ->willReturnSelf(true);

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveAdditionalData($companyId));
    }

    /**
     * @test testSaveAdditionalDataWithDataWithException
     */
    public function testSaveAdditionalDataWithDataWithException()
    {
        $this->expectException(LocalizedException::class);
        $companyId = 31;
        $params = [
            AdditionalData::STORE_ID => 44,
            AdditionalData::STORE_VIEW_ID => 66,
        ];

        $paramsData = [
            Save::IS_PROMO_DISCOUNT_ENABLED => 1,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => 1
        ];


        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(['general'], ['production_location'])
            ->willReturnOnConsecutiveCalls($params, $paramsData);

        //throw exception
        $phrase = new Phrase(__('Same Store view assigned to different Company'));
        $exception = new AlreadyExistsException($phrase);
        $this->additionalDataFactoryMock->expects($this->any())->method('create')->willThrowException($exception);

        $this->saveMock->saveAdditionalData($companyId);
    }

    /**
     * @test saveOrderNotes
     */
    public function testSaveOrderNotes()
    {
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->additionalDataMock]));

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setOrderNotes')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('setStoreViewId')
            ->willReturnSelf();

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturn(1);

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveOrderNotes('hello', false, $this->additionalDataMock));
        $this->assertEquals(false, $this->saveMock->saveOrderNotes('hello'));
    }

    /**
     * @test saveOrderNotes
     */
    public function testSaveOrderNotesToggleOff()
    {

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturn(0);

        $this->assertEquals(false, $this->saveMock->saveOrderNotes('hello'));
    }

    /**
     * @test testSaveCompanySelfRegDataWithAdminApproval
     */
    public function testSaveCompanySelfRegDataWithAdminApproval()
    {
        $companyId = 31;

        $companySelfRegData = ['enable_selfreg' => 1];
        $authRuleData = [
            'self_reg_login_method' => 'admin_approval',
            'domains' => 'google.com',
            'error_message' => 'error message'
        ];

        $this->companySelfRegDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companySelfRegDataCollectionMock);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturn(true);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->companySelfRegDataMock]));

        $this->companySelfRegDataMock->expects($this->any())
            ->method('setSelfRegData')
            ->willReturnSelf();

        $this->companySelfRegDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveCompanySelfRegData($companySelfRegData, $authRuleData, $companyId));
    }

    /**
     * @test testSaveCompanySelfRegDataWithDelete
     */
    public function testSaveCompanySelfRegDataWithDelete()
    {
        $companyId = 31;

        $companySelfRegData = ['enable_selfreg' => 0];
        $authRuleData = [
            'self_reg_login_method' => 'admin_approval',
            'domains' => 'google.com',
            'error_message' => 'error message'
        ];

        $this->companySelfRegDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companySelfRegDataCollectionMock);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->companySelfRegDataMock]));

        $this->companySelfRegDataMock->expects($this->any())
            ->method('setSelfRegData')
            ->willReturnSelf();

        $this->companySelfRegDataMock->expects($this->any())
            ->method('delete')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveCompanySelfRegData($companySelfRegData, $authRuleData, $companyId));
    }

    /**
     * @test testSaveAdditionalDataWithNewData
     */
    public function testSaveCompanySelfRegDataWithNewData()
    {
        $companyId = 31;
        $companySelfRegData = ['enable_selfreg' => 1];
        $authRuleData = [
            'self_reg_login_method' => 'registered_user',
            'domains' => 'google.com',
            'error_message' => 'error message'
        ];

        // B-1385081
        $this->companySelfRegDataFactoryMock->method('create')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls($this->companySelfRegDataMock, $this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companySelfRegDataCollectionMock);

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companySelfRegDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companySelfRegDataMock);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('getId')
            ->willReturn(0);

        $this->companySelfRegDataMock->expects($this->any())
            ->method('setCompanyId')
            ->willReturnSelf();

        $this->companySelfRegDataMock->expects($this->any())
            ->method('setSelfRegData')
            ->willReturnSelf();

        $this->companySelfRegDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->returnSelf($this->saveMock->saveCompanySelfRegData($companySelfRegData, $authRuleData, $companyId));
    }

    /**
     * @test testSaveCompanyPaymentData
     */
    public function testSaveCompanyPaymentData()
    {
        $paymentData = [
            "company_payment_options" => ["fedexaccountnumber", "creditcard"],
            "fedex_account_options" => "custom_fedex_account",
            "creditcard_options" => "new_credit_card",
            "default_payment_method" => "creditcard",
        ];

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('company_payment_methods')
            ->willReturn($paymentData);

        $this->saveMock->saveCompanyPaymentData($this->additionalDataMock);
    }

    /**
     * @test testSaveCompanyPaymentDataWithSingleOption
     */
    public function testSaveCompanyPaymentDataWithSingleOption()
    {
        $paymentData = [
            "company_payment_options" => ["fedexaccountnumber"],
            "fedex_account_options" => "custom_fedex_account",
            "creditcard_options" => "new_credit_card",
            "default_payment_method" => "creditcard",
        ];

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('company_payment_methods')
            ->willReturn($paymentData);

        $this->saveMock->saveCompanyPaymentData($this->additionalDataMock);
    }

    /**
     * B-1393086 | Increase code coverage for module 'Company'
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithPaymentOptions()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'both', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];
        $params = [
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //~ self::ACCEPTANCE_OPTION => 'both123',
            self::RULE_CODE_E => ['key' => 'value'],
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            'company_payment_options' => 1,
            'payment_option' => PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS,
            'is_restricted' => 0,
            'rule_load' => true,
            self::IMAGE_FIELD => [] ,
            self::IS_SENSETIVE_DATA_ENABLED => true,
            self::COMPANY_URL_EXTENTION => 'infogain',
            Save::IS_PROMO_DISCOUNT_ENABLED => true,
            Save::IS_ACCOUNT_DISCOUNT_ENABLED => true
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
         $this->requestMock->method('getParam')
            ->withConsecutive(
                ['id'],
                ['general'],
                ['authentication_rule'],
                ['catalog_document'],
                ['company_payment_methods']
            )->willReturnOnConsecutiveCalls(
                $companyId, $params, $authData, $userSetting, ['fxo_shipping_account_number' => '']
            );

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);
        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);
        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->authDynamicRowsMock]));
        $this->authDynamicRowsMock->expects($this->any())->method('delete')->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
    }

    /**
     * B-1393086 | Increase code coverage for module 'Company'
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithContactAcceptanceOption()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'contact', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];
        $params = [

            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //~ self::ACCEPTANCE_OPTION => 'both',
            self::RULE_CODE_E => ['key' => 'value'],
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            'company_payment_options' => 1,
            'payment_option' => PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS,
            'is_restricted' => 0,
            'rule_load' => true
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(
                ['id'],
                ['general'],
                ['authentication_rule'],
                ['catalog_document'],
                ['company_payment_methods']
            )->willReturnOnConsecutiveCalls(
                $companyId,
                $params,
                $authData,
                $userSetting,
                ['fxo_shipping_account_number' => '']
            );

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);
        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);
        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->authDynamicRowsMock]));
        $this->authDynamicRowsMock->expects($this->any())->method('delete')->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
    }

    /**
     * B-1393086 | Increase code coverage for module 'Company'
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteWithExtrinsicAcceptanceOption()
    {
        $companyId = 1;
        $authData = ['acceptance_option' => 'extrinsic', 'hidden_auth_flag' => 1];
        $userSetting = ['allow_own_document' => 0, 'allow_shared_catalog' => 1];
        $params = [

            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::STATUS => 1,
            CompanyInterface::NAME => 'Example Company Name',
            CompanyInterface::LEGAL_NAME => 'Example Company Name',
            CompanyInterface::COMPANY_EMAIL => 'exampl@test.com',
            CompanyInterface::EMAIL => 'exampl@test.com',
            CompanyInterface::VAT_TAX_ID => '',
            CompanyInterface::RESELLER_ID => '2',
            CompanyInterface::COMMENT => '',
            CompanyInterface::STREET => '',
            CompanyInterface::CITY => '',
            CompanyInterface::COUNTRY_ID => '',
            CompanyInterface::REGION => '',
            CompanyInterface::REGION_ID => '',
            CompanyInterface::POSTCODE => '',
            CompanyInterface::TELEPHONE => '',
            CompanyInterface::JOB_TITLE => '',
            CompanyInterface::PREFIX => '',
            CompanyInterface::FIRSTNAME => '',
            CompanyInterface::MIDDLENAME => '',
            CompanyInterface::LASTNAME => '',
            CompanyInterface::SUFFIX => '',
            CompanyInterface::GENDER => '',
            CompanyInterface::CUSTOMER_GROUP_ID => '',
            CompanyInterface::SALES_REPRESENTATIVE_ID => '',
            CompanyInterface::REJECT_REASON => '',
            CustomerInterface::WEBSITE_ID => '',
            self::DOMAIN_NAME => '',
            self::NETWORK_ID => 'Network1',
            self::COMPANY_URL => '',
            //~ self::ACCEPTANCE_OPTION => 'both',
            self::RULE_CODE_E => ['key' => 'value'],
            self::RULE_CODE_C => ['key' => 'value'],
            self::IS_DELIVERY => '',
            self::IS_PICKUP => '',
            self::RECIPIENT_ADDRESS_FROM_PO => 1,
            self::ENABLE_UPLOAD_SECTION => '',
            self::ENABLE_CATALOG_SECTION => '',
            // Self::RULE_LOAD => '',
            self::SITE_NAME => '',
            self::IS_QUOTE_REQUEST => '',
            self::IS_EXPIRING_ORDER => '',
            self::IS_EXPIRED_ORDER => '',
            self::IS_ORDER_REJECT => '',
            self::ALLOW_OWN_DOCUMENT => '',
            self::ALLOW_SHARED_CATALOG => '',
            self::PAYMENT_OPTION => 'accountnumbers',
            self::FEDEX_ACCOUNT_NUMBER => '',
            self::SHIPPING_ACCOUNT_NUMBER => '',
            self::DISCOUNT_ACCOUNT_NUMBER => '',
            self::ORDER_COMPLETE_CONFIRM => '',
            self::SHIPNOTF_DELIVERY => '',
            self::ORDER_CANCEL_CUSTOMER => '',
            'company_payment_options' => 1,
            'payment_option' => PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS,
            'is_restricted' => 0,
            'rule_load' => true
        ];

        $mainData = [
            'id' => $companyId,
            'general' => $params,
            'authentication_rule' => $authData,
            'catalog_document' => $userSetting,
            'use_default' => 1,
            'company_admin' => ['key', 'value']
        ];

        // B-1385081
        $this->requestMock->method('getParam')
            ->withConsecutive(
                ['id'], ['general'], ['authentication_rule'], ['catalog_document'], ['company_payment_methods'])
            ->willReturnOnConsecutiveCalls(
                $companyId, $params, $authData, $userSetting, ['fxo_shipping_account_number' => '']
            );

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($mainData);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->companyInterfaceMock);

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);
        $this->rowsCollectionMock = $this->createMock(RowsCollection::class);
        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->rowsCollectionMock);
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->rowsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->authDynamicRowsMock]));
        $this->authDynamicRowsMock->expects($this->any())->method('delete')->willReturnSelf();

        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
    }
    /**
     * Save company addtional data
     *
     * @return void
     */
    public function testLoginCommercialStorefrontCheckWithSSO()
    {
        $loginCommercialStorefront = array (
            'storefront_login_method' => 'commercial_store_sso',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_c' => [
                0 => 'Name',
                1 => 'Email',
                ],
            );
        $this->companyRepositoryInterfaceMock->expects($this->any())
        ->method('get')->willReturn($this->companyInterfaceMock);
        $this->assertNull(
            $this->saveMock->loginCommercialStorefrontCheck($loginCommercialStorefront, $this->companyInterfaceMock)
        );
    }

    /**
     * Save company addtional data
     *
     * @return void
     */
    public function testLoginCommercialStorefrontCheckWithSSOWithFCL()
    {
        $loginCommercialStorefront = array (
            'storefront_login_method' => 'commercial_store_sso_with_fcl',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_c' => [
                0 => 'Name',
                1 => 'Email',
                ],
            );
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('xmen_enable_sso_group_authentication_method')
            ->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())
        ->method('get')->willReturn($this->companyInterfaceMock);
        $this->assertNull(
            $this->saveMock->loginCommercialStorefrontCheck($loginCommercialStorefront, $this->companyInterfaceMock)
        );
    }

    /**
     * Save company addtional data
     *
     * @return void
     */
    public function testExecuteRefactorWithEProLoginMethod()
    {
        $loginCommercialStorefront = array (
            'storefront_login_method' => 'commercial_store_epro',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'profile_api_url' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_c' => [
                0 => 'Name',
                1 => 'Email',
                ],
            );
        $this->requestMock->expects($this->any())->method('getParam')->willReturn($loginCommercialStorefront);
        $this->assertNotNull($this->saveMock->executeRefactor($this->requestMock));
    }

    /**
     * Save company addtional data
     *
     * @return void
     */
    public function testExecuteRefactorWithvalidateUserSettingsif()
    {
        $loginCommercialStorefront = array (
            'storefront_login_method' => 'commercial_store_epro',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'profile_api_url' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_e' => [
                'key' => 'value',
                'key1' => 'value1',
                'key2' => 'value2'
                ],
            );
        $aut = array(
            'allow_own_document' => '0',
            'allow_shared_catalog' => '0',
        );
        $this->requestMock->expects($this->any())
        ->method('getParam')->withConsecutive(['authentication_rule'], ['catalog_document'])
        ->willReturnOnConsecutiveCalls($loginCommercialStorefront, $aut);
        $this->assertNotNull($this->saveMock->executeRefactor($this->requestMock));
    }

    /**
     * Save company addtional data
     *
     * @return void
     */
    public function testExecuteRefactorWithvalidateUserSettingselse()
    {
        $loginCommercialStorefront = array (
            'storefront_login_method' => 'commercial_store_epro',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'profile_api_url' => '',
            'acceptance_option' => 'extrinsic',
            'hidden_auth_flag' => '1',
            'rule_load' => '1',
            'rule_code_e' => [
                'key' => 'value',
                'key1' => 'value1',
                'key2' => 'value2'
                ],
            );
        $aut = array(
            'allow_own_document' => '1',
            'allow_shared_catalog' => '1',
        );
        $this->requestMock->expects($this->any())
        ->method('getParam')->withConsecutive(['authentication_rule'], ['catalog_document'])
        ->willReturnOnConsecutiveCalls($loginCommercialStorefront, $aut);
        $this->assertNotNull($this->saveMock->executeRefactor($this->requestMock));
    }

    /**
     * Email Validation Test
     *
     * @return null
     */
    public function testValidateAndSaveBccEmail()
    {
        $emailData = "test@domain.com,test1@domain.com";
        $emailDataWithWrongEmail = "unhappyathtest";
        $emailDataWithNoEmail = "";
        $this->assertNull($this->saveMock
            ->validateAndSaveBccEmail($this->companyInterfaceMock, $emailData));
        $this->assertNull($this->saveMock
            ->validateAndSaveBccEmail($this->companyInterfaceMock, $emailDataWithWrongEmail));
        $this->assertNull($this->saveMock
            ->validateAndSaveBccEmail($this->companyInterfaceMock, $emailDataWithNoEmail));
    }

    /**
     * Prepare Company Rules For Both Acceptance Option
     *
     * @return void
     */
    public function testPrepareCompanyRulesForBothAcceptanceOption()
    {
        $dataContact = ["rule_code_c"=>
            ['email' => 'vivek2.singh@infogain.com',
            'firstname' => 'vicky',
            'lastname' => 'User']];
        $dataExtrinsic = ["rule_code_e"=>
            ['email' => 'vivek2.singh@infogain.com',
            'firstname' => 'vicky',
            'lastname' => 'User']];
        $id = "test";
        $this->assertNull($this->saveMock->prepareCompanyRulesForBothAcceptanceOption($dataContact, $id));
        $this->assertNull($this->saveMock->prepareCompanyRulesForBothAcceptanceOption($dataExtrinsic, $id));
    }
    /**
     * Email Validation Test
     *
     * @return null
     */
    public function testValidateAndSaveNonStandardCatalogDistributionList()
    {
        $emailData = "test@domain.com,test1@domain.com";
        $emailDataWithWrongEmail = "unhappyathtest";
        $emailDataWithNoEmail = "";
        $this->assertNull($this->saveMock
            ->validateAndSaveNonStandardCatalogDistributionList($this->companyInterfaceMock, $emailData));
        $this->assertNull($this->saveMock
            ->validateAndSaveNonStandardCatalogDistributionList($this->companyInterfaceMock, $emailDataWithWrongEmail));
        $this->assertNull($this->saveMock
            ->validateAndSaveNonStandardCatalogDistributionList($this->companyInterfaceMock, $emailDataWithNoEmail));
    }
    /**
     * Test IsOnlyCreateRootCategory
     *
     * @return void
     */
    public function testIsOnlyCreateRootCategory()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateRootCategory');
        $reflectionMethod->setAccessible(true);

        $requestData1 = ['customer_group_id' => Save::CREATE_NEW_VALUE, 'shared_catalog_id' => 'not_create_new'];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, null, $requestData1));

        $requestData2 = ['customer_group_id' => 'not_create_new', 'shared_catalog_id' => 'not_create_new'];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'not_null', $requestData2));

        $requestData3 = ['customer_group_id' => 'not_create_new', 'shared_catalog_id' => Save::CREATE_NEW_VALUE];
        $this->assertTrue($reflectionMethod->invoke($this->saveMock, null, $requestData3));

        $requestData4 = ['customer_group_id' => 'not_create_new', 'shared_catalog_id' => 'not_create_new'];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, null, $requestData4));

        $requestData5 = ['customer_group_id' => Save::CREATE_NEW_VALUE, 'shared_catalog_id' => 'not_create_new'];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'not_null', $requestData5));

        $requestData6 = ['customer_group_id' => 'not_create_new', 'shared_catalog_id' => Save::CREATE_NEW_VALUE];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'not_null', $requestData6));

        $requestData7 = ['customer_group_id' => Save::CREATE_NEW_VALUE, 'shared_catalog_id' => Save::CREATE_NEW_VALUE];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'not_null', $requestData7));
    }
    /**
     * Test Save Recommended Location With Toggle Off
     *
     * @return void
     */
    public function testSaveRecommendedLocationWithToggleOff()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
        ->with(Save::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION)
        ->willReturn(false);

        $data1 = ['allow_production_location' => true, 'production_location_option' => 'recommended_location_all_locations','is_restricted' => 1];

        $this->saveMock->saveRecomendedLocation($data1, $this->companyInterfaceMock);
        $data2 = [];

        $this->saveMock->saveRecomendedLocation($data2, $this->companyInterfaceMock);
        $data3 = [
            'allow_production_location' => true,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 0
        ];
        $this->saveMock->saveRecomendedLocation($data3, $this->companyInterfaceMock);

        $data4 = [
            'allow_production_location' => true,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 0
        ];

        $this->saveMock->saveRecomendedLocation($data4, $this->companyInterfaceMock);


        $data5 = [
            'allow_production_location' => true,
            'production_location_option' => 'test_option',
            'is_restricted' => 0
        ];

        $this->saveMock->saveRecomendedLocation($data5, $this->companyInterfaceMock);
    }
    /**
     * Test Save Recommended Location With Toggle On
     *
     * @return void
     */
    public function testSaveRecommendedLocationWithToggleOn()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Save::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION)
            ->willReturn(true);

        $data5 = [
            'allow_production_location' => true,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 1
        ];
        $this->saveMock->saveRecomendedLocation($data5, $this->companyInterfaceMock);

        $data6 = [
            'allow_production_location' => true,
            'production_location_option' => 'recommended_location_all_locations',
            'is_restricted' => 0
        ];
        $this->saveMock->saveRecomendedLocation($data6, $this->companyInterfaceMock);

        $data7 = [
            'allow_production_location' => true,
            'production_location_option' => 'test_option'
        ];
        $this->saveMock->saveRecomendedLocation($data7, $this->companyInterfaceMock);
        $data8 = [];
        $this->saveMock->saveRecomendedLocation($data8, $this->companyInterfaceMock);
    }
    /**
     * Test IsOnlyCreateCustomerGroupWithNullIdAndBothCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithNullIdAndBothCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => Save::CREATE_NEW_VALUE,
            'shared_catalog_id' => Save::CREATE_NEW_VALUE,
        ];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, null, $requestData));
    }

    /**
     * Test IsOnlyCreateCustomerGroupWithNullIdAndCustomerGroupCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithNullIdAndCustomerGroupCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => Save::CREATE_NEW_VALUE,
            'shared_catalog_id' => 'existing_shared_catalog_id',
        ];
        $this->assertTrue($reflectionMethod->invoke($this->saveMock, null, $requestData));
    }

    /**
     * Test IsOnlyCreateCustomerGroupWithNullIdAndSharedCatalogCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithNullIdAndSharedCatalogCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => 'existing_customer_group_id',
            'shared_catalog_id' => Save::CREATE_NEW_VALUE,
        ];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, null, $requestData));
    }

    /**
     * Test IsOnlyCreateCustomerGroupWithIdAndBothCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithIdAndBothCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => Save::CREATE_NEW_VALUE,
            'shared_catalog_id' => Save::CREATE_NEW_VALUE,
        ];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'existing_id', $requestData));
    }

    /**
     * Test IsOnlyCreateCustomerGroupWithIdAndCustomerGroupCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithIdAndCustomerGroupCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => Save::CREATE_NEW_VALUE,
            'shared_catalog_id' => 'existing_shared_catalog_id',
        ];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'existing_id', $requestData));
    }

    /**
     * Test IsOnlyCreateCustomerGroupWithIdAndSharedCatalogCreateNew
     *
     * @return void
     */
    public function testIsOnlyCreateCustomerGroupWithIdAndSharedCatalogCreateNew()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'isOnlyCreateCustomerGroup');
        $reflectionMethod->setAccessible(true);
        $requestData = [
            'customer_group_id' => 'existing_customer_group_id',
            'shared_catalog_id' => Save::CREATE_NEW_VALUE,
        ];
        $this->assertFalse($reflectionMethod->invoke($this->saveMock, 'existing_id', $requestData));
    }

    /**
     * Test Extract Customer Data With Customer Data
     *
     * @return void
     */
    public function testExtractCustomerDataWithCustomerData()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'extractCustomerData');
        $reflectionMethod->setAccessible(true);
        $requestParams = [
            'company_admin' => [
                'customer_id' => 123,
                'name' => 'John Doe',
            ],
        ];

        $this->requestMock->method('getParams')
            ->willReturn($requestParams);

        $expectedData = $requestParams['company_admin'];
        $this->assertEquals($reflectionMethod->invoke($this->saveMock), $expectedData);
    }

    /**
     * Test Extract Customer Data Without Customer Data
     *
     * @return void
     */
    public function testExtractCustomerDataWithoutCustomerData()
    {
        $reflectionMethod = new \ReflectionMethod(Save::class, 'extractCustomerData');
        $reflectionMethod->setAccessible(true);
        $requestParams = [
            'test_param' => 'value',
        ];

        $this->requestMock->method('getParams')
            ->willReturn($requestParams);

        $this->assertEquals($reflectionMethod->invoke($this->saveMock), []);
    }

    /**
     * Test Set Company Request Data
     *
     * @return void
     */
    public function testSetCompanyRequestData()
    {
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->getMock();

        $data = [
            'name' => 'Test Company',
            'address' => '123 Test St',
        ];

        $this->dataObjectHelperMock->expects($this->once())
            ->method('populateWithArray')
            ->with(
                $companyMock,
                $data,
                CompanyInterface::class
            );

        $result = $this->saveMock->setCompanyRequestData($companyMock, $data);
        $this->assertSame($companyMock, $result);
    }

    /**
     * Test Company Validation Msg NetworkId Error With CleanupToggleEnabled
     *
     * @return void
     */
    public function testCompanyValidationMsgNetworkIdErrorWithCleanupToggleEnabled()
    {
        $validateNewtworkId = false;
        $validateCompanyName = true;
        $validateCompanyUrlExt = true;
        $data = ['network_id' => '123'];
        $authenticationRuleData = ['network_id' => '123'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->with(
                $this->stringContains('Network id, 123, already assigned to some other company')
            );

        $result = $this->saveMock->companyValidationMsg(
            $validateNewtworkId,
            $validateCompanyName,
            $validateCompanyUrlExt,
            $data,
            $authenticationRuleData
        );

        $this->assertTrue($result);
    }

    /**
     * Test Company Validation Msg NetworkId Error With Cleanup Toggle Disabled
     *
     * @return void
     */
    public function testCompanyValidationMsgNetworkIdErrorWithCleanupToggleDisabled()
    {
        $validateNewtworkId = false;
        $validateCompanyName = true;
        $validateCompanyUrlExt = true;
        $data = ['network_id' => '123'];
        $authenticationRuleData = ['network_id' => '123'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->with(
                $this->stringContains('Network id, 123, already assigned to some other company')
            );

        $result = $this->saveMock->companyValidationMsg(
            $validateNewtworkId,
            $validateCompanyName,
            $validateCompanyUrlExt,
            $data,
            $authenticationRuleData
        );

        $this->assertTrue($result);
    }

    /**
     * Test Company Validation Msg Company Name Error
     *
     * @return void
     */
    public function testCompanyValidationMsgCompanyNameError()
    {
        $validateNewtworkId = true;
        $validateCompanyName = false;
        $validateCompanyUrlExt = true;
        $data = ['company_name' => 'Test Company'];
        $authenticationRuleData = ['network_id' => ''];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->with(
                $this->stringContains('Company, Test Company, already assigned to some other company')
            );

        $result = $this->saveMock->companyValidationMsg(
            $validateNewtworkId,
            $validateCompanyName,
            $validateCompanyUrlExt,
            $data,
            $authenticationRuleData
        );

        $this->assertTrue($result);
    }

    /**
     * Test Company Validation Msg Company Url Extention Error
     *
     * @return void
     */
    public function testCompanyValidationMsgCompanyUrlExtError()
    {
        $validateNewtworkId = true;
        $validateCompanyName = true;
        $validateCompanyUrlExt = true;
        $data = [];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(
                $this->callback(function ($argument) {
                    return $argument->getText() === 'Company URL extention already assigned to some other company.';
                })
            );

        $result = $this->saveMock->companyValidationMsg(
            $validateNewtworkId,
            $validateCompanyName,
            $validateCompanyUrlExt,
            $data,
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Test Company Validation Msg With No Errors Error
     *
     * @return void
     */
    public function testCompanyValidationMsgNoErrors()
    {
        $validateNewtworkId = true;
        $validateCompanyName = true;
        $validateCompanyUrlExt = true;
        $data = [];

        $result = $this->saveMock->companyValidationMsg(
            $validateNewtworkId,
            $validateCompanyName,
            $validateCompanyUrlExt,
            $data,
            []
        );
        $this->assertTrue($result);
    }
}
