<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as GateTokenHelper;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Company\Api\CompanyManagementInterface;
use DateTime;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\UploadToQuote\Block\QuoteDetails;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\MarketplaceProduct\Model\ShopManagement;

class QuoteEmailHelperTest extends TestCase
{
     protected $contextMock;
    protected $scopeConfigMock;
    /**
     * @var (MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $quoteDetails;
    protected $storeManager;
    protected $dateTimeMock;
    protected $quoteMock;
    protected $itemMock;
    protected $curlMock;
    protected $gateTokenHelperMock;
    protected $headerDataMock;
    protected $quoteEmailHelper;
    /**
      * @var RequestInterface|MockObject
      */
    protected $requestMock;

    /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelperMock;

    /**
     * @var Email|MockObject
     */
    protected $emailMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $timezoneInterface;

    /**
     * @var ProductInfoHandler|MockObject
     */
    protected $productInfoHandler;

    /**
     * @var UploadToQuoteViewModel|MockObject
     */
    protected $uploadToQuoteViewModel;

    /**
     * @var CompanyManagementInterface $companyRepository
     */
    protected $companyRepository;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * @var SelfReg
     */
    protected $selfReg;

    /**
     * @var ToggleConfig|(ToggleConfig&object&MockObject)|(ToggleConfig&MockObject)|(object&MockObject)|MockObject
     */
    protected $toggleConfig;

    /**
     * @var CartInterface
     */
    protected $cartMock;

    /**
     * @var ShopManagement
     */
    protected $shopManagement;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScopeConfig'])
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->contextMock->method('getScopeConfig')->willReturn($this->scopeConfigMock);

        $this->loggerMock=$this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteDetails = $this->getMockBuilder(QuoteDetails::class)
            ->setMethods(['isEproCustomer'])
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getByCustomerId',
                    'getCompanyUrlExtention'
                ]
            )
            ->disableOriginalconstructor()
            ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getFromEmail',
                'getQuoteDeclineUserEmail',
                'getQuoteDeclineEmailTemplate',
                'getQuoteChangeRequestUserEmail',
                'getQuoteChangeRequestEmailTemplate',
                'getQuoteReadyForReviewEmailTemplate',
                'getQuoteConfirmationEmailTemplate',
                'getUploadToQuoteEmailConfigValue',
                'getExpiryDate',
                'getFormattedDate',
                'convertPrice',
                'getQuoteDeclineUserEmailEnable',
                'getQuoteChangeRequestUserEmailEnable',
                'getQuoteReadyForReviewEmailEnable',
                'getQuoteConfirmationEmailEnable',
                'isToggleD226511Enabled',
                'isToggleB2564807Enabled',
                'isToggleD235696Enabled',
                'toggleUploadToQuoteSubmitDate',
                'getSubmitDate'

            ])
            ->getMockForAbstractClass();

        $this->emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadEmailTemplate', 'callGenericEmailApi','sendEmail'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->cartMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsBid', 'getNbcRequired'])
            ->getMockForAbstractClass();


        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format','setTimezone'])
            ->getMockForAbstractClass();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['diff'])
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerEmail',
                'getCreatedAt',
                'getAllVisibleItems',
                'getIsEproQuote',
                'getCustomer',
                'getIsBid',
                'getNbcRequired'
            ])
            ->getMockForAbstractClass();

        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductLineItems', 'isItemPriceable','getPriceDash','isQuotePriceable'])
            ->getMockForAbstractClass();

        $this->productInfoHandler = $this->getMockBuilder(ProductInfoHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemExternalProd'])
            ->getMockForAbstractClass();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getName',
                    'getQty',
                    'getPrice',
                    'getOptionByCode',
                    'getValue'
                ]
            )
            ->disableOriginalconstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(
                [
                    'getByCustomerId',
                    'getCompanyUrlExtention'
                ]
            )
            ->disableOriginalconstructor()
            ->getMockForAbstractClass();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(
                [
                    'isFuseBidToggleEnabled',
                    'isBidCheckoutEnabled'
                ]
            )
            ->disableOriginalconstructor()
            ->getMock();

        $this->shopManagement = $this->getMockBuilder(ShopManagement::class)
            ->disableOriginalconstructor()
            ->getMock();

        $this->curlMock = $this->createMock(Curl::class);
        $this->gateTokenHelperMock = $this->createMock(GateTokenHelper::class);
        $this->headerDataMock = $this->createMock(HeaderData::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteEmailHelper = $objectManagerHelper->getObject(
            QuoteEmailHelper::class,
            [
                'context' => $this->contextMock,
                'logger' => $this->loggerMock,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'email' => $this->emailMock,
                'storeManager' => $this->storeManager,
                'quoteRepository' => $this->quoteRepositoryMock,
                'timezoneInterface' => $this->timezoneInterface,
                'productInfoHandler' => $this->productInfoHandler,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'shopManagement' => $this->shopManagement,
                'companyRepository' => $this->companyRepository,
                'fuseBidViewModel' => $this->fuseBidViewModel,
                'curl' => $this->curlMock,
                'gateTokenHelper' => $this->gateTokenHelperMock,
                'headerData' => $this->headerDataMock,
                'quoteDetails' => $this->quoteDetails,
                'selfReg' => $this->selfReg,
                'toggleConfig' => $this->toggleConfig
            ]
        );
        $this->quoteEmailHelper->status = 'confirmed';
    }

    /**
     * Test method for sendQuoteGenericEmail function
     *
     * @return void
     */
    public function testSendQuoteGenericEmail()
    {
        $this->setupCommonExpectations();
        $this->setupCurl();
        $quoteData = ['quote_id' => 123, 'status' => 'confirmed'];
        $this->adminConfigHelperMock->method('getQuoteConfirmationEmailEnable')
        ->willReturn(true);
        $this->emailMock->method('callGenericEmailApi')->willReturnSelf();
        $result = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);

        $this->assertNotNull($result);
    }

    /**
     * Common setup expectations for sendQuoteGenericEmail tests
     *
     * @return void
     */
    private function setupCommonExpectations()
    {
        $companyAttributesMock = $this->createMock(\Magento\Company\Api\Data\CompanyCustomerInterface::class);
        $companyAttributesMock->method('getCompanyId')->willReturn(123);

        $extensionAttributesMock = $this->createMock(\Magento\Customer\Api\Data\CustomerExtensionInterface::class);
        $extensionAttributesMock->method('getCompanyAttributes')->willReturn($companyAttributesMock);

        $customerMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $customerMock->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $customerMock->method('getStoreId')->willReturn('1');

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->adminConfigHelperMock
        ->expects($this->any())
        ->method('getUploadToQuoteEmailConfigValue')
        ->willReturn(true);
        $additionalOption = json_encode([
            'label' => 'fxoProductInstance',
            'value' => '57854580254633540' ]);
            $this->quoteRepositoryMock->method('get')->willReturn($this->quoteMock);
            $this->quoteMock->method('getCustomer')->willReturn($customerMock);
            $this->quoteMock->method('getCustomerEmail')->willReturn('customer@example.com');
            $this->fuseBidViewModel->expects($this->any())->method('isFuseBidToggleEnabled')->willReturn(true);
            $this->quoteMock->method('getCreatedAt')->willReturn('2023-01-01 00:00:00');
            $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
            $this->itemMock->method('getName')->willReturn('Product Name');
            $this->itemMock->method('getQty')->willReturn(2);
            $this->itemMock->method('getPrice')->willReturn(19.99);
            $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturnSelf();
            $this->itemMock->expects($this->any())->method('getValue')->willReturn($additionalOption);
            $this->adminConfigHelperMock->expects($this->any())
            ->method('isToggleB2564807Enabled')->willReturn(true);
            $this->adminConfigHelperMock->expects($this->any())
            ->method('getFormattedDate')->willReturn('2023-01-01 00:00:00');
            $this->adminConfigHelperMock->expects($this->any())
            ->method('getExpiryDate')->willReturn('2023-01-01 00:00:00');
            $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
            $this->timezoneInterface->expects($this->any())->method('setTimezone')->willReturnSelf();
            $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
            $this->dateTimeMock->expects($this->any())->method('diff')->willReturnSelf();
            $this->adminConfigHelperMock->expects($this->any())
            ->method('isToggleD235696Enabled')->willReturn(true);
            $this->uploadToQuoteViewModel->method('isItemPriceable')->willReturn(true);
            $this->uploadToQuoteViewModel->method('isQuotePriceable')->willReturn(true);
            $this->adminConfigHelperMock->method('convertPrice')->willReturn('100');
            $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
            $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
            $this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturnSelf();
            $this->companyRepository->expects($this->any())
            ->method('getCompanyUrlExtention')->willReturn('uploadtoquote-dev');
    }

     /**
      * Test method for testSendQuoteGenericEmailForReviewStatus function
      *
      * @return void
      */
    public function testSendQuoteGenericEmailForReviewStatus()
    {
        $quoteData = ['quote_id' => 123, 'status' => 'submitted_by_admin'];
        $this->setupCommonExpectations();
        $this->setupCurl();

        $result = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);

        $this->assertNull($result);
    }
     /**
      * Test method for sendQuoteGenericEmail function for pricable false
      *
      * @return void
      */
    public function testSendQuoteGenericEmailWithPricableFalse()
    {
        $this->setupCommonExpectations();
        $this->setupCurl();

        $quoteData = ['quote_id' => 123, 'status' => 'confirmed'];
        $result = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);

        $this->assertNull($result);
    }

     /**
      * Test method for prepareGenericEmailRequest function
      *
      * @return void
      */
    public function testPrepareGenericEmailRequest()
    {
        $quoteData = ['quote_id'=>12, 'status'=>'nbc_support'];
        $this->quoteRepositoryMock->method('get')->willReturn($this->cartMock);
        $this->adminConfigHelperMock->expects($this->any())->method('getQuoteConfirmationEmailTemplate')
        ->willReturnSelf();
        $this->emailMock->expects($this->any())->method('loadEmailTemplate')->willReturn("Test");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->quoteMock->expects($this->any())->method('getCustomerEmail')->willReturn("test@fedex.com");
        $this->fuseBidViewModel->expects($this->any())->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getIsBid')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getNbcRequired')->willReturn(true);
        $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
    }

    /**
     * Test method for prepareGenericEmailRequest function
    *
    * @return void
    */
    public function testPrepareGenericEmailRequestNBCPriced()
    {
        $this->setupCommonExpectations();
        $this->setupCurl();
        $quoteData = ['quote_id'=>12, 'status'=>'nbc_priced'];
        $this->fuseBidViewModel->expects($this->any())->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getIsBid')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getNbcRequired')->willReturn(true);
        $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
    }

    /**
     * Test method for getTemplateId function
     *
     * @return void
     */
    public function testGetTemplateId()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn('decline_template_id');
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')
        ->willReturn('decline_by_team_template_id');
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')
        ->willReturn('change_request_template_id');
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn('review_template_id');
        $this->adminConfigHelperMock->method('getQuoteConfirmationEmailTemplate')
        ->willReturn('confirmation_template_id');
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')
            ->willReturn('expired_template_id');
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')
            ->willReturn('expiration_template_id');

        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('declined'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('declined_by_team'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('change_request'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('submitted_by_admin'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('confirmed'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('expiration'));
        $this->assertNotNull($this->quoteEmailHelper->getTemplateId('expired'));

        $this->assertNull($this->quoteEmailHelper->getTemplateId('unknown_status'));
    }

    /**
     * Test method for getQuoteEmailSubject function
     *
     * @return void
     */
    public function testGetQuoteEmailSubject()
    {
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('declined'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('declined_by_team'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('change_request'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('submitted_by_admin'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('confirmed'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('expired'));
        $this->assertNotNull($this->quoteEmailHelper->getQuoteEmailSubject('expiration'));
        $this->assertNull($this->quoteEmailHelper->getQuoteEmailSubject('unknown_status'));
    }

    /**
     * Test method for getQuoteEmailSubject function
     *
     * @return void
     */
    public function testGetQuoteEmailSubject2()
    {
        $result = $this->quoteEmailHelper->getQuoteEmailSubject('nbc_support', true, false);
        $this->assertEquals(QuoteEmailHelper::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — NBC Working On Quote', $result);
    }

    /**
     * Test method for getQuoteEmailSubject function
     *
     * @return void
     */
    public function testGetQuoteEmailSubjectNBCPriced()
    {
        $result = $this->quoteEmailHelper->getQuoteEmailSubject('nbc_priced', true, false);
        $this->assertEquals(QuoteEmailHelper::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — NBC Supported - Quote is Ready', $result);
    }

    /**
     * Test method for getQuoteEmailSubject function
     *
     * @return void
     */
    public function testGetQuoteEmailSubject3()
    {
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $result = $this->quoteEmailHelper->getQuoteEmailSubject('submitted_by_admin', true, false);
        $this->assertEquals(QuoteEmailHelper::FEDEX_OFFICE_FUSE_SUBJECT.' — Quote Ready For Your Review', $result);
    }

     /**
      * Test checkEmailEnable
      *
      * @return void
      */
    public function testCheckEmailEnable()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn(1);

        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('declined'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('change_request'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('submitted_by_admin'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('confirmed'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('expired'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('expiration'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('ordered'));
        $this->assertNotNull($this->quoteEmailHelper->checkEmailEnable('declined_by_team'));
    }

    /**
      * Test checkEmailEnable
      *
      * @return void
      */
      public function testCheckEmailEnableReview()
      {
          $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
          $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
          $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn(1);
          $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
          $result = $this->quoteEmailHelper->checkEmailEnable('submitted_by_admin', true, true);
          $this->assertEquals(1,  $result);
      }

    /**
      * Test checkEmailEnable
      *
      * @return void
      */
    public function testCheckEmailEnableNBCSupport()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn(1);
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $result = $this->quoteEmailHelper->checkEmailEnable('nbc_support', true, true);
        $this->assertEquals(1,  $result);
    }

    /**
     * Test testCheckEmailEnableNBCPriced
    *
    * @return void
    */
    public function testCheckEmailEnableNBCPriced()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->adminConfigHelperMock->method('getUploadToQuoteEmailConfigValue')->willReturn(1);
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $result = $this->quoteEmailHelper->checkEmailEnable('nbc_priced', true, true);
        $this->assertEquals(1,  $result);
    }
    /**
     * Test IsEproCustomer
     * @return int
     */
    public function testIsEproCustomer()
    {
        $this->quoteDetails->expects($this->any())->method('isEproCustomer')->willReturnSelf();
        $this->assertNotNull($this->quoteEmailHelper->isEproCustomer());
    }

    /**
     * Test getEmailTemplate
     *
     * @return void
     */
    public function testGetEmailTemplate()
    {
        $quoteData = [
            'quote_id' => 12345,
            'status' => 'submitted_by_admin',
            'is_bid' => true,
            'nbc' => false
        ];
        $this->setupCurl();
        $this->setupCommonExpectations();
        $this->quoteRepositoryMock->method('get')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->fuseBidViewModel->method('isBidCheckoutEnabled')->willReturn(true);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('setTimezone')->willReturnSelf();
        $this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturnSelf();
            $this->companyRepository->expects($this->any())
            ->method('getCompanyUrlExtention')->willReturn('uploadtoquote-dev');
        $this->adminConfigHelperMock->expects($this->any())->method('isToggleD226511Enabled')
        ->willReturn(true);
        $this->adminConfigHelperMock->expects($this->any())->method('getFormattedDate')
        ->willReturn('2023-01-01 00:00:00');
        $this->adminConfigHelperMock->expects($this->any())->method('getExpiryDate')
        ->willReturn('2023-01-01 00:00:00');
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);

        $this->assertNull($this->quoteEmailHelper->getEmailTemplate($quoteData));
    }

    private function setupCurl()
    {
        $apiUrl = 'http://example.com/api/location_details';
        $apiUrlFinal = 'http://example.com/api/location_details/?startDate='.date("m-d-Y").'&views=30';
        $responseBody = json_encode([
            'output' => [
                'address' => [
                    'address1' => '123 Main St',
                    'address2' => 'Suite 100',
                    'city' => 'Anytown',
                    'stateOrProvinceCode' => 'CA',
                    'postalCode' => '12345'
                ],
                'phone' => '123-456-7890',
                'email' => 'store@example.com'
            ]
        ]);

        $this->scopeConfigMock->method('getValue')->willReturn($apiUrl);
        $this->gateTokenHelperMock->method('getAuthGatewayToken')->willReturn('token');
        $this->headerDataMock->method('getAuthHeaderValue')->willReturn('Bearer ');

        $this->curlMock->method('setOptions')
            ->with($this->arrayHasKey(CURLOPT_HTTPHEADER));

        $this->curlMock->method('get')
            ->with($apiUrlFinal);

        $this->curlMock->method('getBody')
            ->willReturn($responseBody);
    }
}


