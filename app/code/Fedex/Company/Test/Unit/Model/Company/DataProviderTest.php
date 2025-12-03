<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Model\Company;

use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AuthDynamicRows;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\CompanyData;
use Fedex\Company\Model\Company\DataProvider;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection;
use Fedex\SelfReg\Model\CompanySelfRegData;
use Fedex\SelfReg\Model\CompanySelfRegDataFactory;
use Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData\Collection as CompanySelfRegDataCollection;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AttributeMetadataResolver;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Shipto\Model\ProductionLocation as ProductionLocationModel;
use PHPUnit\Framework\MockObject\MockObject;

class DataProviderTest extends TestCase
{
    protected $customerRepository;
    protected $customerInterface;
    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyCollectionFactory;
    /**
     * @var (\Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $extensionAttributesJoinProcessor;
    /**
     * @var (\Magento\Eav\Model\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eavConfig;
    /**
     * @var (\Magento\Customer\Model\AttributeMetadataResolver & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeMetadataResolver;
    protected $json;
    /**
     * @var (\Fedex\Company\Model\AdditionalDataFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataFactoryMock;
    /**
     * @var (\Fedex\Company\Model\AdditionalData & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataMock;
    /**
     * @var (\Fedex\Company\Model\ResourceModel\AdditionalData\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataCollectionMock;
    protected $companySelfRegDataFactoryMock;
    protected $companySelfRegDataMock;
    protected $companySelfRegDataCollectionMock;
    protected $companyDataMock;
    protected $companyRepositoryInterfaceMock;
    protected $productionLocationFactoryMock;
    protected $productionLocationMock;
    protected $toggleConfigMock;
    protected $productionLocationCollectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const IS_BANNER_ENABLE = 'is_banner_enable';
    const BANNER_TITLE = 'banner_title';
    const ICONOGRAPHY = 'iconography';
    const BANNER_DESCRIPTION = 'description';
    const BANNER_CTA_TEXT = 'cta_text';
    const BANNER_CTA_LINK = 'cta_link';
    const BANNER_LINK_OPEN_IN_NEW_TAB = 'link_open_in_new_tab';

    const NOTIFICATION_PARAM_DATA = [
        self::IS_BANNER_ENABLE => 1,
        self::BANNER_TITLE => 1,
        self::ICONOGRAPHY => 'warning' ,
        self::BANNER_DESCRIPTION => 'dfs',
        self::BANNER_CTA_TEXT => 1,
        self::BANNER_CTA_LINK => 'ddsfs',
        self::BANNER_LINK_OPEN_IN_NEW_TAB => 1
    ];

    protected $dataProvider;

    protected $companyInterfaceMock;

    private $authDynamicRowsFactoryMock;

    protected $authDynamicRowsCollection;

    protected $authDynamicRowsMock;

    protected $groupRepositoryMock;

    protected $storeRepositoryMock;

    protected $groupInterfaceMock;

    protected $storeInterfaceMock;
    private MockObject|CompanyCustomerInterface $companyCustomerInterfaceMock;
    private MockObject|CustomerExtensionInterface $customerExtensionInterfaceMock;

    protected function setUp(): void
    {
        $this->authDynamicRowsCollection = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->authDynamicRowsMock = $this
            ->getMockBuilder(AuthDynamicRows::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authDynamicRowsFactoryMock = $this->createMock(AuthDynamicRowsFactory::class);//B-1326233

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCompanyName',
                    'getStatus',
                    'getRejectReason',
                    'getRejectedAt',
                    'getCompanyEmail',
                    'getSalesRepresentativeId',
                    'getDomainName',
                    'getNetworkId',
                    'getCompanyUrl',
                    'getOrderCompleteConfirm',
                    'getShipnotfDelivery',
                    'getOrderCancelCustomer',
                    'getPaymentOption',
                    'getFedexAccountNumber',
                    'getShippingAccountNumber',
                    'getDiscountAccountNumber',
                    'getAllowOwnDocument',
                    'getAllowSharedCatalog',
                    'getAllowUploadAndPrint',
                    'getNonStandardCatalogDistributionList',
                    'getAllowNonStandardCatalog',
                    'getAllowUploadToQuote',
                    'getUploadToQuoteNextStepContent',
                    'getAllowNextStepContent',
                    'getIsQuoteRequest',
                    'getIsExpiringOrder',
                    'getIsExpiredOrder',
                    'getIsOrderReject',
                    'getIsSuccessEmailEnable',
                    'getBccCommaSeperatedEmail',
                    'getOrderConfirmationEmailTemplate',
                    'getAcceptanceOption',
                    'getId',
                    'getSiteName',
                    'getIsDelivery',
                    'getIsPickup',
                    'getHcToggle',
                    'getRecipientAddressFromPo',
                    'getEnableUploadSection',
                    'getEnableCatalogSection',
                    'getAllowedDeliveryOptions',
                    'getCompanyLogo',
                    'getIsSensitiveDataEnabled',
                    'getCompanyUrlExtention',
                    'getStorefrontLoginMethodOption',
                    'getSsoLoginUrl',
                    'getSsoLogoutUrl',
                    'getSsoIdp',
                    'getSsoGroup',
                    'getIsPromoDiscountEnabled',
                    'getIsAccountDiscountEnabled',
                    'getIsCatalogMvpEnabled',
                    'getIsReorderEnabled',
                    'getFxoAccountNumberEditable',
                    'getShippingAccountNumberEditable',
                    'getDiscountAccountNumberEditable',
                    'getBoxEnabled',
                    'getDropboxEnabled',
                    'getGoogleEnabled',
                    'getMicrosoftEnabled',
                    'getSharedCatalogId',
                    'getAllowProductionLocation',
                    'getSelfRegData',
                    'getIsEproU2QEnabled',
                    'getData',
                    'getOfficeSuppliesEnabled',
                    'getShippingPackingMailingEnabled'
                ]
            )
            ->getMockForAbstractClass();

        $this->companyCollectionFactory = $this->getMockBuilder(CompanyCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extensionAttributesJoinProcessor = $this->getMockBuilder(JoinProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeMetadataResolver = $this->getMockBuilder(AttributeMetadataResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['getData', 'getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId']
            )
            ->getMock();

        $this->additionalDataCollectionMock = $this
            ->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId', 'getData'])
            ->getMock();

        $this->companySelfRegDataFactoryMock = $this->getMockBuilder(CompanySelfRegDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companySelfRegDataMock = $this->getMockBuilder(CompanySelfRegData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getData',
                'getCollection',
                'addFieldToSelect',
                'addFieldToFilter',
                'getFirstItem',
                'getSelfRegData'
                ])
            ->getMock();

        $this->companySelfRegDataCollectionMock = $this->getMockBuilder(CompanySelfRegDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getSelfRegData'])
            ->getMock();

        $this->groupRepositoryMock = $this
            ->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->storeRepositoryMock = $this
            ->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->groupInterfaceMock = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyDataMock = $this->getMockBuilder(CompanyData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalData','getSharedCatalogId'])
            ->getMockForAbstractClass();

        $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productionLocationFactoryMock = $this->createMock(ProductionLocationFactory::class);

        $this->productionLocationMock =  $this->getMockBuilder(ProductionLocationModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getData','create'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock =  $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMockForAbstractClass();

        $this->productionLocationCollectionMock =
            $this->getMockBuilder(\Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection::class)
            ->setMethods(['addFieldToFilter','getData'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->dataProvider = $this->objectManager->getObject(
            DataProvider::class,
            [
                'name' => 'test-name',
                'primaryFieldName' => 'primary-field-name',
                'requestFieldName' => 'request-field-name',
                'companyCollectionFactory' => $this->companyCollectionFactory,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'customerRepository' => $this->customerRepository,
                'eavConfig' => $this->eavConfig,
                'attributeMetadataResolver' => $this->attributeMetadataResolver,
                'json' => $this->json,
                'companyData' => $this->companyDataMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'companySelfRegDataFactory' => $this->companySelfRegDataFactoryMock,
                'groupRepository' => $this->groupRepositoryMock,
                'storeRepository' => $this->storeRepositoryMock,
                'companyRepository' => $this->companyRepositoryInterfaceMock,
                'productionlocationFactory' => $this->productionLocationFactoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                [],
                [],
            ]
        );
    }

    /**
     * Test for getAuthenticationData method.
     *
     * @return array
     */
    public function testGetAuthenticationData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getAcceptanceOption')
            ->willReturn(1);

        $testData = ['enable_selfreg' => 1, 'self_reg_login_method' => '', 'domains' => '', 'error_message' => ''];
        $jsonData = '{"enable_selfreg":"1","self_reg_login_method":"","domains":"","error_message":""}';
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
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->json->expects($this->any())->method('unserialize')->willReturn($testData);

        $data = [
            'acceptance_option' => 1,
            'hidden_auth_flag' => 0,
            'storefront_login_method' => '',
            'sso_login_url' => '',
            'sso_logout_url' => '',
            'sso_idp' => '',
            'sso_group' => '',
            'self_reg_login_method' => '',
            'domains' => '',
            'fcl_user_email_verification_error_message' => '',
            'fcl_user_email_verification_user_display_message' => '',
            'error_message' => '',
            'domain_name' => '',
            'network_id' => '',
            'site_name' => ''
        ];

        $this->assertNotEquals($data, $this->dataProvider->getAuthenticationData($this->companyInterfaceMock));
    }

    /**
     * Test for getAuthenticationData method.
     *
     * @return array
     */
    public function testGetAuthenticationDataWithToggleOn()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getAcceptanceOption')
            ->willReturn(1);
        $testData = ['enable_selfreg' => 1, 'self_reg_login_method' => '', 'domains' => '', 'error_message' => ''];
        $jsonData = '{"enable_selfreg":"1","self_reg_login_method":"","domains":"","error_message":""}';
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
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->json->expects($this->any())->method('unserialize')->willReturn($testData);
        $this->assertNotNull($this->dataProvider->getAuthenticationData($this->companyInterfaceMock));
    }

    public function getAuthRuleFlag(CompanyInterface $company)
    {
        return 0;
    }

    /**
     * Test for getAuthRuleFlag method.
     *
     * @return void
     */
    public function testGetAuthRuleFlag()
    {
        $this->companyInterfaceMock->expects($this->any())->method('getAcceptanceOption')->willReturn('both');
        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->authDynamicRowsMock);
        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')
        ->willReturn($this->authDynamicRowsCollection);
        $this->authDynamicRowsCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->authDynamicRowsCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
    }

    /**
     * Test for getEmailNotificationData method.
     *
     * @return array
     */
    public function testGetEmailNotificationData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsQuoteRequest')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsExpiringOrder')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsExpiredOrder')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsOrderReject')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsSuccessEmailEnable')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getBccCommaSeperatedEmail')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getOrderConfirmationEmailTemplate')
            ->willReturn(1);

        $data = [
            'is_quote_request' => 1,
            'is_expiring_order' => 1,
            'is_expired_order' => 1,
            'is_order_reject' => 1,
            'is_success_email_enable' => 1,
            'bcc_comma_seperated_email' => 1,
            'order_confirmation_email_template' => 1
        ];

        $this->assertEquals($data, $this->dataProvider->getEmailNotificationData($this->companyInterfaceMock));
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for getCatalogAndDocumentsData method.
     *
     * @return array
     */
    public function testGetCatalogAndDocumentsData()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $testData = ['is_reorder_enabled' => 1];

        $this->companyDataMock->expects($this->any())->method('getAdditionalData')->willReturn($testData);
        $this->companyInterfaceMock->expects($this->any())->method('getSharedCatalogId')->willReturn(9);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowOwnDocument')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowSharedCatalog')
            ->willReturn(1);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowNonStandardCatalog')
            ->willReturn(1);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowUploadAndPrint')
            ->willReturn(1);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getNonStandardCatalogDistributionList')
            ->willReturn(1);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getBoxEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getDropboxEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getGoogleEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getMicrosoftEnabled')
            ->willReturn(1);

        $data = [
            'is_reorder_enabled' => 1,
            'allow_own_document' => 1,
            'allow_shared_catalog' => 1,
            'allow_upload_and_print' => 1,
            'allow_non_standard_catalog' => 1,
            'non_standard_catalog_distribution_list' => 1,
            'box_enabled' => 1,
            'dropbox_enabled' => 1,
            'google_enabled' => 1,
            'microsoft_enabled' => 1,
            'shared_catalog_id' => 9,
            'all_print_products_cms_block_identifier'=> '',
            'office_supplies_enabled' => null,
            'shipping_packing_mailing_enabled' => null
        ];

        $this->assertEquals($data, $this->dataProvider->getCatalogAndDocumentsData($this->companyInterfaceMock));
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for getCatalogAndDocumentsData method.
     *
     * @return array
     */
    public function testGetCatalogAndDocumentsDataWithToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(0);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowOwnDocument')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowSharedCatalog')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowNonStandardCatalog')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowUploadAndPrint')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getBoxEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getDropboxEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getGoogleEnabled')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getMicrosoftEnabled')
            ->willReturn(1);

        $data = [
            'is_reorder_enabled' => 1,
            'allow_own_document' => 1,
            'allow_shared_catalog' => 1,
            'box_enabled' => 1,
            'dropbox_enabled' => 1,
            'google_enabled' => 1,
            'microsoft_enabled' => 1,
        ];

        $this->assertNotEquals($data, $this->dataProvider->getCatalogAndDocumentsData($this->companyInterfaceMock));
    }

    /**
     * Test for getUploadToQuoteData method.
     *
     * @return array
     */
    public function testGetUploadToQuoteData()
    {
        $returnData = 'Print instruction message';

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
        ->method('getUploadToQuoteNextStepContent')
        ->willReturn($returnData);

        $this->companyInterfaceMock->expects($this->any())
        ->method('getAllowNextStepContent')
        ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getAllowUploadToQuote')
            ->willReturn(1);

        $data = [
            'allow_upload_to_quote' => 1,
            'allow_next_step_content'=> 1,
            'upload_to_quote_next_step_content' => 'Print instruction message',
        ];

        $this->assertEquals($data, $this->dataProvider->getUploadToQuoteData($this->companyInterfaceMock));
    }

    /**
     * Test for getPaymentMethodsData method.
     *
     * @return array
     */
    public function testGetPaymentMethodsData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getPaymentOption')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn('1234');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getDiscountAccountNumber')
            ->willReturn('3456');

        $data = [
            'payment_option' => 1,
            'fedex_account_number' => 1,
            'shipping_account_number' => '1234',
            'discount_account_number' => '3456',
        ];

        $this->assertEquals($data, $this->dataProvider->getPaymentMethodsData($this->companyInterfaceMock));
    }

    /**
     * Test for getCxmlNotificationData method.
     *
     * @return array
     */
    public function testGetCxmlNotificationData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getOrderCompleteConfirm')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getShipnotfDelivery')
            ->willReturn(1);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getOrderCancelCustomer')
            ->willReturn(1);

        $data = [
            'order_complete_confirm' => 1,
            'shipnotf_delivery' => 1,
            'order_cancel_customer' => 1,
        ];

        $this->assertEquals($data, $this->dataProvider->getCxmlNotificationData($this->companyInterfaceMock));
    }

    /**
     * Test for getGlobalSettingsData method.
     *
     * @return array
     */
    public function testGetGlobalSettingsData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getSiteName')
            ->willReturn('testeprosite');
        $data = [
            'site_name' => 'testeprosite',
        ];
        $this->assertEquals($data, $this->dataProvider->getGlobalSettingsData($this->companyInterfaceMock));
    }

    /**
     * Test for getGlobalSettingsData method.
     *
     * @return array
     */
    public function testGetShippingOptionsData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsDelivery')
            ->willReturn('1');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsPickup')
            ->willReturn('1');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getHcToggle')
            ->willReturn('1');

        $data = [
            'is_delivery' => 1,
            'is_pickup' => 1,
            'hc_toggle' => 1,
            'recipient_address_from_po' => null,
            'allowed_delivery_options' => null,
        ];
        $this->assertEquals($data, $this->dataProvider->getShippingOptionsData($this->companyInterfaceMock));
    }

    /**
     * Test for getGlobalSettingsData method.
     *
     * @return array
     */
    public function testGetHomepageSettingsData()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getEnableUploadSection')
            ->willReturn('1');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getEnableCatalogSection')
            ->willReturn('1');

        $data = [
            'enable_upload_section' => 1,
            'enable_catalog_section' => 1,
        ];
        $this->assertEquals($data, $this->dataProvider->getHomepageSettingsData($this->companyInterfaceMock));
    }

    /**
     * @test testGetCompanySelfRegData
     */
    public function testGetCompanySelfRegData()
    {
        $testData = [
            'enable_selfreg' => 1,
            'self_reg_login_method' => 'registered_user',
            'domains' => 'google.com',
            'fcl_user_email_verification_error_message' => null,
            'fcl_user_email_verification_user_display_message' => null,
            'error_message' => 'error message'
        ];
        $jsonData = '{
            "enable_selfreg":"1",
            "self_reg_login_method":"registered_user",
            "domains":"google.com",
            "error_message":"error"
        }';
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
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getSelfRegData')
            ->willReturn($jsonData);

        $this->json->expects($this->any())->method('unserialize')->willReturn($testData);

        $this->assertEquals($testData, $this->dataProvider->getCompanySelfRegData($this->companyInterfaceMock));
    }

    /**
     * @test testGetCompanySelfRegDataWithEmptyData
     */
    public function testGetCompanySelfRegDataWithEmptyData()
    {
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
            ->willReturn(null);

        $this->assertEquals([], $this->dataProvider->getCompanySelfRegData($this->companyInterfaceMock));
    }

    /**
     * @test testGetStoreDetails
     */
    public function testGetStoreDetails()
    {
        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];
        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $this->companyDataMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($testData);

        $this->groupRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->groupInterfaceMock);

        $this->groupInterfaceMock->expects($this->any())
            ->method('getName')
            ->willReturn($testData['store_name']);

        $this->storeRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getName')
            ->willReturn($testData['store_view_name']);
        $this->assertEquals($testData, $this->dataProvider->getStoreDetails($this->companyInterfaceMock));
    }

    /**
     * @test testGetStoreDetails
     */
    public function testGetNewStoreDetails()
    {
        $testData = [
            'id' => 1,
            'company_id' => 31,
            'new_store_view_id' => 65,
            'new_store_id' => 1,
            'new_store_name' => 'test',
            'new_store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];
        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $this->companyDataMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($testData);

        $this->groupRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->groupInterfaceMock);

        $this->groupInterfaceMock->expects($this->any())
            ->method('getName')
            ->willReturn($testData['new_store_name']);

        $this->storeRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getName')
            ->willReturn($testData['new_store_view_name']);
        $this->assertEquals($testData, $this->dataProvider->getNewStoreDetails($this->companyInterfaceMock));
    }

    /**
     * @test testGetPaymentData
     */
    public function testGetPaymentData()
    {
        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];
        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn('1');
        $this->companyInterfaceMock->expects($this->any())
            ->method('getFxoAccountNumberEditable')
            ->willReturn('1');
        $this->companyInterfaceMock->expects($this->any())
            ->method('getShippingAccountNumberEditable')
            ->willReturn('1');
        $this->companyInterfaceMock->expects($this->any())
            ->method('getDiscountAccountNumberEditable')
            ->willReturn('1');
        $this->companyDataMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($testData);
        $this->dataProvider->getPaymentData($this->companyInterfaceMock);
    }

    /**
     * @test testGetPaymentData
     */
    public function testGetPaymentData1()
    {
        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => '',
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];
        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn('1');
        $this->companyDataMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($testData);
        $this->dataProvider->getPaymentData($this->companyInterfaceMock);
    }

    /**
     * @test testGetPaymentDataWithEmptyAdditionalData
     */
    public function testGetPaymentDataWithEmptyAdditionalData()
    {
        $this->companyDataMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn([]);
        $this->dataProvider->getPaymentData($this->companyInterfaceMock);
    }

    /**
     * @test testgetProductionLocationToggleOn
     * Toggle false case
     */
    public function testgetProductionLocationToggleOn()
    {
        $this->companyInterfaceMock->expects($this->any())->method('getId')->willReturn(8);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturnSelf();
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')->with(8)
        ->willReturn($this->companyInterfaceMock);

        $testData = [
            'id' => 1,
            'company_id' => 31,
            'new_store_view_id' => 65,
            'new_store_id' => 1,
            'new_store_name' => 'test',
            'new_store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
            'is_promo_discount_enabled' => 1,
            'is_account_discount_enabled' => 1,
            'order_notes' => 'test',
            'terms_and_conditions' => 1
        ];

        $this->companyDataMock->expects($this->any())->method('getAdditionalData')->willReturn($testData);

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
        ->willReturn($this->productionLocationCollectionMock);

        $this->productionLocationCollectionMock->expects($this->any())->method('addFieldToFilter')
        ->willReturnSelf();
        $this->productionLocationCollectionMock->expects($this->any())->method('getData')
        ->willReturn(['test']);
        $this->dataProvider->getProductionLocation($this->companyInterfaceMock);
    }

    /**
     * @test testgetProductionLocationToggleOffCases
     * Toggle false case
     */
    public function testgetProductionLocationToggleOffCases()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
         ->willReturnOnConsecutiveCalls(0);

        $this->companyInterfaceMock->expects($this->any())->method('getId')->willReturn(8);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturnSelf();
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')->with(8)
        ->willReturn($this->companyInterfaceMock);

        $testData = [
            'id' => 1,
            'company_id' => 31,
            'new_store_view_id' => 65,
            'new_store_id' => 1,
            'new_store_name' => 'test',
            'new_store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
            'is_promo_discount_enabled' => 1,
            'is_account_discount_enabled' => 1,
            'order_notes' => 'test',
            'terms_and_conditions' => 1
        ];

        $this->companyDataMock->expects($this->any())->method('getAdditionalData')->willReturn($testData);

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
        ->willReturn($this->productionLocationCollectionMock);

        $this->productionLocationCollectionMock->expects($this->any())->method('addFieldToFilter')
        ->willReturnSelf();
        $this->productionLocationCollectionMock->expects($this->any())->method('getData')
        ->willReturn(['test']);

        $this->assertNotNull($this->dataProvider->getProductionLocation($this->companyInterfaceMock));
    }

    /**
     * @test testgetProductionLocationToggleOn
     * Toggle false case
     */
    public function testgetProductionLocationToggleOn1()
    {
        $this->companyInterfaceMock->expects($this->any())->method('getId')->willReturn(8);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('get')->with(8)
        ->willReturn($this->companyInterfaceMock);

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->productionLocationMock);

        $this->productionLocationMock->expects($this->any())->method('getCollection')
        ->willReturn($this->productionLocationCollectionMock);

        $this->productionLocationCollectionMock->expects($this->any())->method('addFieldToFilter')
        ->willReturnSelf();
        $this->productionLocationCollectionMock->expects($this->any())->method('getData')
        ->willReturn(['test']);
        $this->dataProvider->getProductionLocation($this->companyInterfaceMock);
    }

    /**
     * @test testgetProductionLocationToggleOff
     * Toggle false case
     */
    public function testgetProductionLocationToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(0);
        $this->dataProvider->getProductionLocation($this->companyInterfaceMock);
    }

    /**
     * @test testgetCompanyLogoSettingToggleOn
     * Toggle true case
     */
    public function testgetCompanyLogoSettingToggleOn()
    {
        $companyLogo = '{"type":"image/png","name":"Fedex_Office.png","size":"123","url":"Fedex_Office.png","previewType":"image","id":""}';
        $this->companyInterfaceMock->expects($this->any())
            ->method('getCompanyLogo')->willReturn($companyLogo);

       $this->dataProvider->getCompanyLogoSetting($this->companyInterfaceMock);
    }

    /**
     * @test testisMvpCatalogEnabledToggleOn
     * Toggle true case
     */
    public function testisMvpCatalogEnabledToggleOn()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsCatalogMvpEnabled')->willReturn(1);

        $this->assertNotNull($this->dataProvider->isMvpCatalogEnabled($this->companyInterfaceMock));
    }
     /**
     * @test testisMvpCatalogEnabledToggleOff
     * Toggle false case
     */
    public function testisMvpCatalogEnabledToggleOff()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getIsCatalogMvpEnabled')->willReturn(1);

        $this->assertNotNull($this->dataProvider->isMvpCatalogEnabled($this->companyInterfaceMock));
    }

    /**
     * @test testgetNotificationBannerData
     */
    public function testGetNotificationBannerData()
    {
        $this->companyDataMock->expects($this->any())
        ->method('getAdditionalData')
        ->willReturn(self::NOTIFICATION_PARAM_DATA);

        $this->assertNotNull($this->dataProvider->getNotificationBannerData($this->companyInterfaceMock));
    }

    /**
     * @test testisEproU2QEnabledToggleOn
     * Toggle true case
     */
    public function testisEproU2QEnabledToggleOn()
    {
        $this->companyInterfaceMock->expects($this->any())
            ->method('getData')->willReturnSelf();
        $this->assertNotNull($this->dataProvider->isEproU2QEnabled($this->companyInterfaceMock));
    }
}
