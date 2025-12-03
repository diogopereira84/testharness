<?php

/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Company\Model\AdditionalData;
use Magento\Framework\UrlInterface;
use Magento\User\Model\UserFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;
use Magento\User\Model\User;
use Fedex\Company\Helper\ExportCompanyData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Country;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\AuthDynamicRows;
use Fedex\Company\Model\AuthDynamicRows\Collection;
use Magento\Catalog\Model\Category;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\NegotiableQuote\Api\CompanyQuoteConfigManagementInterface;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Unit tests for Export Company Helper Data.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportCompanyDataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $additionalDataMock;
    protected $regionFactoryMock;
    protected $regionModelMock;
    protected $countryFactoryMock;
    protected $countryModelMock;
    protected $userFactoryMock;
    protected $userModelMock;
    protected $urlInterfaceMock;
    protected $creditDataProviderInterfaceMock;
    protected $creditDataInterfaceMock;
    protected $groupRepositoryInterfaceMock;
    protected $groupInterfaceMock;
    protected $companyQuoteConfigMock;
    protected $companyQuoteConfigInterfaceMock;
    protected $companyInterfaceMock;
    protected $companyRepositoryInterfaceMock;
    protected $customerRepository;
    protected $attributeValueMock;
    protected $customer;
    protected $ruleFactoryMock;
    protected $ruleMock;
    protected $authCollectionMock;
    protected $categoryModel;
    protected $toggleConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $exportCompanyDataMock;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyAdditionalData',
                'getIsReorderEnabled',
                'getOrderNotes',
                'getTermsAndConditions',
                'getIsPromoDiscountEnabled',
                'getIsAccountDiscountEnabled',
                'getEproNewPlatformOrderCreation',
                'getCompanyPaymentOptions',
                'getDefaultPaymentMethod',
                'getFedexAccountOptions',
                'getCreditcardOptions',
                'getIsBannerEnable',
                'getBannerTitle',
                'getDescription',
                'getIconography',
                'getCtaText',
                'getCtaLink',
                'getLinkOpenInNewTab'
            ])->getMock();

        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->regionModelMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getName'])
            ->getMock();

        $this->countryFactoryMock = $this->getMockBuilder(CountryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->countryModelMock = $this->getMockBuilder(Country::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCode', 'getName'])
            ->getMock();

        $this->userFactoryMock = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->userModelMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getUsername'])
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $this->creditDataProviderInterfaceMock = $this->getMockBuilder(CreditDataProviderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->creditDataInterfaceMock = $this->getMockBuilder(CreditDataInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrencyCode', 'getCreditLimit', 'getExceedLimit'])
            ->getMockForAbstractClass();

        $this->groupRepositoryInterfaceMock = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->groupInterfaceMock = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMockForAbstractClass();

        $this->companyQuoteConfigMock = $this->getMockBuilder(CompanyQuoteConfigManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCompanyId'])
            ->getMockForAbstractClass();

        $this->companyQuoteConfigInterfaceMock = $this->getMockBuilder(CompanyQuoteConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsQuoteEnabled'])
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsPurchaseOrderEnabled'])
            ->getMockForAbstractClass();

        $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getData', 'getDomainName', 'getNetworkId', 'getSiteName',
                'getAcceptanceOption', 'getCompanyName', 'getStatus', 'getCompanyEmail',
                'getSalesRepresentativeId', 'getCompanyUrlExtention', 'getIsSensitiveDataEnabled',
                'getCompanyLogo', 'getOrderCompleteConfirm', 'getShipnotfDelivery',
                'getOrderCancelCustomer', 'getExtensionAttributes', 'getAllowOwnDocument',
                'getAllowSharedCatalog', 'getAllowUploadToQuote', 'getBoxEnabled', 'getDropboxEnabled',
                'getGoogleEnabled', 'getMicrosoftEnabled', 'getContentSquare', 'getAdobeAnalytics',
                'getAppDynamics', 'getForsta', 'getIsQuoteRequest', 'getIsExpiringOrder', 'getIsExpiredOrder',
                'getIsOrderReject', 'getIsSuccessEmailEnable', 'getBccCommaSeperatedEmail',
                'getOrderConfirmationEmailTemplate','getUploadToQuoteNextStepContent',
                'getAllowNextStepContent', 'getFedexAccountNumber', 'getFxoAccountNumberEditable',
                'getShippingAccountNumber', 'getShippingAccountNumberEditable', 'getDiscountAccountNumber',
                'getDiscountAccountNumberEditable', 'getIsDelivery', 'getAllowedDeliveryOptions',
                'getIsPickup', 'getHcToggle', 'getRecipientAddressFromPo', 'getLegalName', 'getVatTaxId',
                'getResellerId', 'getComment', 'getStreet', 'getCity', 'getRegionId', 'getCountryId',
                'getPostcode', 'getTelephone', 'getCustomerGroupId', 'getIsCatalogMvpEnabled',
                'getCustomBillingShipping', 'getCustomBillingInvoiced', 'getCustomBillingCreditCard',
                'getSuperUserId', 'getStorefrontLoginMethodOption', 'getSsoLoginUrl', 'getSsoLogoutUrl',
                'getSsoIdp', 'getSsoGroup', 'getNuance'
            ])
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'getExtensionAttributes'])
            ->getMockForAbstractClass();

        $this->attributeValueMock = $this->getMockBuilder(\Magento\Customer\Model\AttributeValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getCustomAttribute'])
            ->getMockForAbstractClass();

        $this->ruleFactoryMock = $this->getMockBuilder(AuthDynamicRowsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->ruleMock = $this->getMockBuilder(AuthDynamicRows::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getCollection', 'getType', 'getRuleCode', 'addFieldToSelect', 'addFieldToFilter'])
            ->getMock();

        $this->authCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToSelect', 'addFieldToFilter', 'getIterator'])
            ->getMock();

        $this->categoryModel = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->exportCompanyDataMock = $this->objectManager->getObject(
            ExportCompanyData::class,
            [
                'context' => $this->contextMock,
                'urlInterface' => $this->urlInterfaceMock,
                'userFactory' => $this->userFactoryMock,
                'regionFactory' => $this->regionFactoryMock,
                'customerRepository' => $this->customerRepository,
                'countryFactory' => $this->countryFactoryMock,
                'ruleFactory' => $this->ruleFactoryMock,
                'creditDataProviderInterface' => $this->creditDataProviderInterfaceMock,
                'groupRepositoryInterface' => $this->groupRepositoryInterfaceMock,
                'companyQuoteConfigManagementInterface' => $this->companyQuoteConfigMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * TestExportCompanyGeneralTab
     *
     * @return json
     */
    public function testExportCompanyGeneralTab()
    {
        $data = [
            'company_name' => 'test',
            'status' => 'Active',
            'company_email' => 'test@gmail.com',
            'sales_representative' => 'testadmin@gmail.com',
            'url_extention' => 'src',
            'sensitive_data_enabled' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCompanyName')->willReturn('test');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getStatus')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCompanyEmail')
        ->willReturn('test@gmail.com');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getSalesRepresentativeId')->willReturn(1);
        $this->testGetSalesRepresentativeEmail();
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCompanyUrlExtention')
        ->willReturn('src');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsSensitiveDataEnabled')
        ->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportCompanyGeneralTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * Test Get Company Status
     *
     * @return string
     */
    public function testGetCompanyStatusWithActive()
    {
        $this->assertEquals('Active', $this->exportCompanyDataMock->getCompanyStatus(1));
    }

    /**
     * Test Get Company Status
     *
     * @return string
     */
    public function testGetCompanyStatusWithRejected()
    {
        $this->assertEquals('Rejected', $this->exportCompanyDataMock->getCompanyStatus(2));
    }

    /**
     * Test Get Company Status
     *
     * @return string
     */
    public function testGetCompanyStatusWithBlocked()
    {
        $this->assertEquals('Blocked', $this->exportCompanyDataMock->getCompanyStatus(3));
    }

    /**
     * Test Get Company Status
     *
     * @return string
     */
    public function testGetCompanyStatusWithPending()
    {
        $this->assertEquals('Pending Approval', $this->exportCompanyDataMock->getCompanyStatus(4));
    }

    /**
     * Test Get Sales Representative Email
     *
     * @return string
     */
    public function testGetSalesRepresentativeEmail()
    {
        $result = 'testadmin@gmail.com';

        $this->userFactoryMock->expects($this->any())->method('create')->willReturn($this->userModelMock);
        $this->userModelMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userModelMock->expects($this->any())->method('getUsername')->willReturn($result);

        $this->assertEquals($result, $this->exportCompanyDataMock->getSalesRepresentativeEmail(1));
    }

    /**
     * testExportUiCustomization
     *
     * @return string
     */
    public function testExportUiCustomization()
    {
        $data = ['url' => 'test'];
        $result = json_encode($data);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCompanyLogo')->willReturn($result);
        $this->urlInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('hhhhh/');
        $logoData = ['logo_image' => 'hhhhhtest'];
        $this->assertEquals(
            json_encode($logoData),
            $this->exportCompanyDataMock->exportUiCustomization($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * testExportUiCustomizationWithEmpty
     *
     * @return string
     */
    public function testExportUiCustomizationWithEmpty()
    {
        $data = ['url' => ''];
        $result = json_encode($data);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCompanyLogo')->willReturn($result);
        $this->urlInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('hhhhh/');
        $logoData = ['logo_image' => ''];
        $this->assertEquals(
            json_encode($logoData),
            $this->exportCompanyDataMock->exportUiCustomization($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportCatalogAndDocumentTab
     *
     * @return json
     */
    public function testExportCatalogAndDocumentTab()
    {
        $data = [
            'reorder' => 1,
            'allow_own_document' => 1,
            'allow_shared_catalog' => 1,
            'box_cloud_drive_integration_option' => 1,
            'dropbox_cloud_drive_integration_option' => 1,
            'google_cloud_drive_integration_option' => 1,
            'microsoft_cloud_drive_integration_option' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getIsReorderEnabled')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAllowSharedCatalog')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getBoxEnabled')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getDropboxEnabled')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getGoogleEnabled')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getMicrosoftEnabled')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportCatalogAndDocumentTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestGetCompanyAdditionalDataObj
     *
     * @return json
     */
    public function testGetCompanyAdditionalDataObj()
    {
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();

        $this->assertNotNull(
            $this->exportCompanyDataMock->getCompanyAdditionalDataObj($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportCxmlNotificationTab
     *
     * @return json
     */
    public function testExportCxmlNotificationTab()
    {
        $data = [
            'order_complete_confirm' => 1,
            'shipping_notification_or_delivery_options' => 1,
            'order_cancel_customer' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getOrderCompleteConfirm')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getShipnotfDelivery')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getOrderCancelCustomer')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportCxmlNotificationTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportFxoWebAnalyticsTab
     *
     * @return json
     */
    public function testExportFxoWebAnalyticsTab()
    {
        $data = [
            'content_square' => 1,
            'adobe_analytics' => 1,
            'app_dynamics' => 1,
            'forsta' => 1,
            'nuance' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getContentSquare')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAdobeAnalytics')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAppDynamics')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getForsta')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getNuance')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportFxoWebAnalyticsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportEmailNotificationOptionsTab
     *
     * @return json
     */
    public function testExportEmailNotificationOptionsTab()
    {
        $data = [
            'is_quote_request' => 1,
            'is_expiring_order_email' => 1,
            'is_expired_order_email' => 1,
            'is_order_reject_email' => 1,
            'is_success_email_enable' => 1,
            'bcc_comma_seperated_email' => 'dummy@gmail.com, abc@gmail.com',
            'order_confirmation_email_template' => '0'
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsQuoteRequest')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsExpiringOrder')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsExpiredOrder')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsOrderReject')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsSuccessEmailEnable')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getBccCommaSeperatedEmail')
        ->willReturn('dummy@gmail.com, abc@gmail.com');
        $this->companyRepositoryInterfaceMock->expects($this->any())
        ->method('getOrderConfirmationEmailTemplate')->willReturn('0');
        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportEmailNotificationOptionsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportUploadToQuoteTab
     *
     * @return json
     */
    public function testExportUploadToQuoteTab()
    {
        $data = [
            'allow_upload_to_quote' => 1,
            'enable_next_step_content_to_display' => 1,
            'upload_to_quote_next_step_content' => 'test'
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAllowUploadToQuote')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAllowNextStepContent')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getUploadToQuoteNextStepContent')
        ->willReturn('test');

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportUploadToQuoteTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportOrderSettingsTab
     *
     * @return json
     */
    public function testExportOrderSettingsTab()
    {
        $data = [
            'order_notes' => 'test',
            'terms_and_conditions' => 1,
            'is_promo_discount_enabled' => 1,
            'is_account_discount_enabled' => 1,
            "epro_new_platform_order_creation" => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getOrderNotes')->willReturn('test');
        $this->additionalDataMock->expects($this->any())->method('getTermsAndConditions')->willReturn(1);
        $this->additionalDataMock->expects($this->any())->method('getIsPromoDiscountEnabled')->willReturn(1);
        $this->additionalDataMock->expects($this->any())->method('getIsAccountDiscountEnabled')->willReturn(1);
        $this->additionalDataMock->expects($this->any())->method('getEproNewPlatformOrderCreation')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportOrderSettingsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportPaymentMethodsTab
     *
     * @return json
     */
    public function testExportPaymentMethodsTab()
    {
        $fedexShippingReferenceField = [
            [
                "record_id" => "0",
                "field_name" => "fedex_shipping_reference_1",
                "field_label" => "test123",
                "default" => "Test",
                "visible" => 1,
                "editable" => 1,
                "required" => 1,
                "mask" => [
                    [
                        "record_id" => "0",
                        "field_name" => "fedex_shipping_reference_1",
                        "field_label" => "test123",
                        "default" => "Test",
                        "visible" => 1,
                        "editable" => 1,
                        "required" => 1,
                        "mask" => [
                            "record_id" => "0",
                            "field_name" => "fedex_shipping_reference_1",
                            "field_label" => "test123",
                            "default" => "Test",
                            "visible" => 1,
                            "editable" => 1,
                            "required" => 1,
                            "mask" => [
                                "record_id" => "0",
                                "field_name" => "fedex_shipping_reference_1",
                                "field_label" => "test123",
                                "default" => "Test",
                                "visible" => 1,
                                "editable" => 1,
                                "required" => 1
                            ],
                            "custom_mask" => "test",
                            "error_message" => "numbers",
                            "position" => 1,
                            "initialize" => 1
                        ],
                        "custom_mask" => "test",
                        "error_message" => "numbers",
                        "position" => 1,
                        "initialize" => 1
                    ]
                ],
                "custom_mask" => "test",
                "error_message" => "numbers",
                "position" => 1,
                "initialize" => 1
            ]
        ];

        $customBillingFieldsInvoicedAccount = [
            [
                "record_id" => "0",
                "field_name" => "IA_Reference_2",
                "field_label" => "Last_Name",
                "default" => "K",
                "visible" => 1,
                "editable" => 1,
                "required" => 1,
                "custom_mask" => "^[0-9]{6,6}$",
                "error_message" => "required field",
                "position" => "2",
                "initialize" => "true",
                "mask" => "No"
            ]
        ];

        $customBillingFieldsCreditCard = [
            [
                "record_id" => "0",
                "field_name" => "CC_Reference_1",
                "field_label" => "Nativity",
                "default" => "Asian",
                "visible" => 1,
                "editable" => 1,
                "required" => 0,
                "mask" => "No",
                "custom_mask" => "^[0-9]{6,6}$",
                "error_message" => "Only 6 letter word allowed",
                "position" => "1",
                "initialize" => "true"
            ],
            [
                "record_id" => "1",
                "field_name" => "CC_Reference_2",
                "field_label" => "Qualification",
                "default" => "Graduate",
                "visible" => 1,
                "editable" => 0,
                "required" => 1,
                "mask" => "validate-number",
                "custom_mask" => "^\\s*-?\\d*(\\.\\d*)?\\s*$",
                "error_message" => "Invisible Field",
                "position" => "2",
                "initialize" => "true"
            ]
        ];

        $data = [
            'applicable_payment_method' => ['fedexaccountnumber', 'creditcard'],
            'default_payment_method' => 'fedexaccountnumber',
            'fedex_account_options' => 'custom_fedex_account',
            'fxo_account_number' => ExportCompanyData::MASK_DIGIT,
            'fxo_account_number_editable' => 1,
            'shipping_account_number' => ExportCompanyData::MASK_DIGIT,
            'shipping_account_number_editable' => 1,
            'discount_account_number' => ExportCompanyData::MASK_DIGIT,
            'discount_account_number_editable' => 1,
            'credit_card_options' => 'new_credit_card',
            'fedex_shipping_reference_field' => $fedexShippingReferenceField,
            'custom_billing_fields_invoiced_account' => $customBillingFieldsInvoicedAccount,
            'custom_billing_fields_credit_card' => $customBillingFieldsCreditCard
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getCompanyPaymentOptions')
        ->willReturn(['fedexaccountnumber', 'creditcard']);
        $this->additionalDataMock->expects($this->any())->method('getDefaultPaymentMethod')
        ->willReturn('fedexaccountnumber');
        $this->additionalDataMock->expects($this->any())->method('getFedexAccountOptions')
        ->willReturn('custom_fedex_account');
        $this->additionalDataMock->expects($this->any())->method('getCreditcardOptions')
        ->willReturn('new_credit_card');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getFedexAccountNumber')
        ->willReturn(ExportCompanyData::MASK_DIGIT);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getFxoAccountNumberEditable')
        ->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getShippingAccountNumber')
        ->willReturn(ExportCompanyData::MASK_DIGIT);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getShippingAccountNumberEditable')
        ->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getDiscountAccountNumber')
        ->willReturn(ExportCompanyData::MASK_DIGIT);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getDiscountAccountNumberEditable')
        ->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCustomBillingShipping')
        ->willReturn(json_encode($fedexShippingReferenceField));
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCustomBillingInvoiced')
        ->willReturn(json_encode($customBillingFieldsInvoicedAccount));
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCustomBillingCreditCard')
        ->willReturn(json_encode($customBillingFieldsCreditCard));

        $this->assertNotNull(
            $result,
            $this->exportCompanyDataMock->exportPaymentMethodsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportNotificationBannerTab
     *
     * @return json
     */
    public function testExportNotificationBannerTab()
    {
        $data = [
            'is_banner_enable' => 1,
            'banner_title' => 'test',
            'description' => 'testing data',
            'iconography' => 'Warning',
            'cta_text' => 'test cta',
            'cta_link' => 'testing link',
            'link_open_in_new_tab' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getIsBannerEnable')->willReturn(1);
        $this->additionalDataMock->expects($this->any())->method('getBannerTitle')->willReturn('test');
        $this->additionalDataMock->expects($this->any())->method('getDescription')->willReturn('testing data');
        $this->additionalDataMock->expects($this->any())->method('getIconography')->willReturn('warning');
        $this->additionalDataMock->expects($this->any())->method('getCtaText')->willReturn('test cta');
        $this->additionalDataMock->expects($this->any())->method('getCtaLink')->willReturn('testing link');
        $this->additionalDataMock->expects($this->any())->method('getLinkOpenInNewTab')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportNotificationBannerTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportDeliveryOptionsTab
     *
     * @return json
     */
    public function testExportDeliveryOptionsTab()
    {
        $allowedShippingOptions = [
            'GROUND_US',
            'LOCAL_DELIVERY_AM',
            'LOCAL_DELIVERY_PM',
            'EXPRESS_SAVER',
            'TWO_DAY',
            'STANDARD_OVERNIGHT',
            'PRIORITY_OVERNIGHT',
            'FIRST_OVERNIGHT'
        ];

        $data = [
            'shipment' => 1,
            'allowed_shipping_options' => $allowedShippingOptions,
            'store_pickup' => 1,
            'hc_toggle' => 1,
            'recipient_address_from_po' => 1
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsDelivery')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getAllowedDeliveryOptions')
        ->willReturn($allowedShippingOptions);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsPickup')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getHcToggle')->willReturn(1);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getRecipientAddressFromPo')
        ->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportDeliveryOptionsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportAccountInformationTab
     *
     * @return json
     */
    public function testExportAccountInformationTab()
    {
        $data = [
            'legal_name' => 'bmw',
            'vat_tax_id' => '12',
            'reseller_id' => '23',
            'comment' => 'dummy data'
        ];

        $result = json_encode($data);

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getLegalName')->willReturn('bmw');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getVatTaxId')
        ->willReturn('12');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getResellerId')->willReturn('23');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getComment')->willReturn('dummy data');

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportAccountInformationTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportLegalAddressTab
     *
     * @return json
     */
    public function testExportLegalAddressTab()
    {
        $data = [
            'street' => 'Shiv Nagar',
            'city' => 'Plano',
            'country_id' => 'United States',
            'state_or_province' => 'Texas',
            'postcode' => '75024',
            'telephone' => '9988778797'
        ];

        $result = json_encode($data);

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionModelMock);
        $this->regionModelMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionModelMock->expects($this->any())->method('getName')->willReturn('Texas');
        $this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryModelMock);
        $this->countryModelMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryModelMock->expects($this->any())->method('getName')->willReturn('United States');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getStreet')->willReturn('Shiv Nagar');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCity')->willReturn('Plano');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCountryId')->willReturn('US');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getRegionId')->willReturn(60);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getPostcode')->willReturn('75024');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getTelephone')->willReturn('9988778797');

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportLegalAddressTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * Test Get Customer Status
     *
     * @return string
     */
    public function testGetCustomerStatusWithInActive()
    {
        $this->assertEquals('Inactive', $this->exportCompanyDataMock->getCustomerStatus(0));
    }

    /**
     * Test Get Customer Status
     *
     * @return string
     */
    public function testGetCustomerStatusWithActive()
    {
        $this->assertEquals('Active', $this->exportCompanyDataMock->getCustomerStatus(1));
    }

    /**
     * Test Get Customer Status
     *
     * @return string
     */
    public function testGetCustomerStatusWithPending()
    {
        $this->assertEquals('Pending For Approval', $this->exportCompanyDataMock->getCustomerStatus(2));
    }

    /**
     * Test Get Website Status
     *
     * @return string
     */
    public function testGetWebsiteStatus()
    {
        $this->assertEquals('Main Website', $this->exportCompanyDataMock->getWebsiteStatus(1));
    }

    /**
     * Export Authentication Tab With Epro Data
     */
    public function testExportAuthenticationTabWithEproData()
    {
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getStorefrontLoginMethodOption')
        ->willReturn('commercial_store_epro');
        $this->ruleFactoryMock->expects($this->any())->method('create')->willReturn($this->ruleMock);
        $this->ruleMock->expects($this->any())->method('getCollection')->willReturn($this->authCollectionMock);
        $this->authCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->authCollectionMock->expects($this->any())->method('addFieldToFilter')
        ->willReturn(new \ArrayIterator([$this->ruleMock]));
        $this->authCollectionMock->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->categoryModel]));
        $this->categoryModel->expects($this->any())->method('getId')->willReturn('123');
        $this->ruleMock->expects($this->any())->method('getType')->willReturnSelf();
        $this->ruleMock->expects($this->any())->method('getRuleCode')->willReturnSelf();

        $this->assertNotNull(
            $this->exportCompanyDataMock->exportAuthenticationTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * Export Authentication Tab With Wlgn Data
     */
    public function testExportAuthenticationTabWithWlgnData()
    {
        $selfRegData = '{
            "enable_selfreg":1,
            "self_reg_login_method":"admin_approval",
            "domains":"",
            "error_message":"Your access request has been submitted"
        }';

        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getStorefrontLoginMethodOption')
        ->willReturn('commercial_store_wlgn');
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getData')->with('self_reg_data')
        ->willReturn($selfRegData);

        $this->assertNotNull(
            $this->exportCompanyDataMock->exportAuthenticationTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * Export Authentication Tab With SSO Data
     */
    public function testExportAuthenticationTabWithSsoData()
    {
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getStorefrontLoginMethodOption')
        ->willReturn('commercial_store_sso');

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('xmen_enable_sso_group_authentication_method')
            ->willReturn(1);

        $this->assertNotNull(
            $this->exportCompanyDataMock->exportAuthenticationTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * Export CompanyAdmin Tab
     */
    public function testExportCompanyAdminTab()
    {
        $userId = 4;
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getSuperUserId')->willReturn($userId);
        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);

        $this->assertNotNull(
            $this->exportCompanyDataMock->exportCompanyAdminTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportCompanyCreditTab
     *
     * @return json
     */
    public function testExportCompanyCreditTab()
    {
        $data = [
            'credit_currency' => 'US Dollar',
            'credit_limit' => '0.0',
            'allow_to_exceed_credit_limit' => "1"
        ];

        $result = json_encode($data);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getId')->willReturn(48);
        $this->creditDataProviderInterfaceMock->expects($this->any())->method('get')
        ->willReturn($this->creditDataInterfaceMock);
        $this->creditDataInterfaceMock->expects($this->any())->method('getCurrencyCode')->willReturn('US Dollar');
        $this->creditDataInterfaceMock->expects($this->any())->method('getCreditLimit')->willReturn('0.0');
        $this->creditDataInterfaceMock->expects($this->any())->method('getExceedLimit')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportCompanyCreditTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportAdvancedSettingsTab
     *
     * @return json
     */
    public function testExportAdvancedSettingsTab()
    {
        $data = [
            'customer_group' => 'group name',
            'allow_quotes' => '1',
            'enable_purchase_orders' => '1'
        ];

        $result = json_encode($data);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getCustomerGroupId')->willReturn(90);
        $this->groupRepositoryInterfaceMock->expects($this->any())->method('getById')
        ->willReturn($this->groupInterfaceMock);
        $this->groupInterfaceMock->expects($this->any())->method('getCode')->willReturn('group name');
        $this->companyQuoteConfigMock->expects($this->any())->method('getByCompanyId')
        ->willReturn($this->companyQuoteConfigInterfaceMock);
        $this->companyQuoteConfigInterfaceMock->expects($this->any())->method('getIsQuoteEnabled')->willReturn(true);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getIsPurchaseOrderEnabled')->willReturn(true);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportAdvancedSettingsTab($this->companyRepositoryInterfaceMock)
        );
    }

    /**
     * TestExportMvpCatalogSettingTab
     *
     * @return json
     */
    public function testExportMvpCatalogSettingTab()
    {
        $data = [
            'is_catalog_mvp_enabled' => 1
        ];

        $result = json_encode($data);
        $this->companyRepositoryInterfaceMock->expects($this->any())->method('getIsCatalogMvpEnabled')->willReturn(1);

        $this->assertEquals(
            $result,
            $this->exportCompanyDataMock->exportMvpCatalogSettingTab($this->companyRepositoryInterfaceMock)
        );
    }
}
