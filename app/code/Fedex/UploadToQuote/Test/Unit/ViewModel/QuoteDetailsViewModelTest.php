<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\UploadToQuote\Test\Unit\ViewModel;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\ViewModel\QuoteDetailsViewModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\MarketplacePunchout\Model\ProductInfo;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\MarketplaceCheckout\Helper\Data as Config;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\FXOCMConfigurator\Helper\Data;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

class QuoteDetailsViewModelTest extends TestCase
{
    protected $serializerMock;
    protected $response;
    protected $fxoRateQuoteHelper;
    protected $quoteItem;
    protected $option;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\TimezoneInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $timezone;
    /**
     * @var (\Fedex\MarketplacePunchout\Model\ProductInfo & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productInfo;
    /**
     * @var (\Fedex\ExpiredItems\Helper\ExpiredItem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $expiredItem;
    protected $config;
    /**
     * @var (\Fedex\ExpiredItems\Model\ConfigProvider & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configProvider;
    protected $dataHelperMock;
    protected $quoteDetailsViewModelData;
    /**
     * @var Quote $quote
     */
    protected $quote;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Http $request
     */
    protected $request;

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected AdminConfigHelper $adminConfigHelper;

    /**
     * @var GraphqlApiHelper $graphqlApiHelper
     */
    protected GraphqlApiHelper $graphqlApiHelper;

    /**
     * @var CustomerSession $customerSession;
     */
    protected CustomerSession $customerSession;

    /**
     * @var ResponseFactory $responseFactory
     */
    protected $responseFactory;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    public function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getId', 'getAllVisibleItems', 'save'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getAllVisibleItems'])
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUploadToQuoteId', 'getBackUrl'])
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->onlyMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphqlApiHelper = $this->getMockBuilder(GraphqlApiHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['getQuoteNotes'])
        ->getMock();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExpiryDate',
                'getFormattedDate',
                'convertPrice',
                'getQuoteStatusLabel',
                'getNegotiableQuoteStatus',
                'getQuoteStatusKeyByQuoteId',
                'getUploadToQuoteConfigValue',
                'updateQuoteStatusByKey',
                'getPercentBasedOnStatus',
                'getDeletedItems',
                'getStatusDataByQuoteId',
                'getTotalPriceInfForRemainingItem',
                'getProductValue',
                'getProductJson',
                'isU2QCustomerSIEnabled',
                'getProductAttributeName',
                'productImageUrl',
                'checkIsPunchoutQuote',
                'isToggleD206707Enabled'
            ])
            ->getMock();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect', 'sendResponse'])
            ->getMock();

        $this->fxoRateQuoteHelper = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getOptionByCode', 'getItemId', 'getOptionId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionId', 'setValue', 'getValue', 'getOptionByCode'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getBaseUrl'])
            ->getMockForAbstractClass();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->productInfo = $this->getMockBuilder(ProductInfo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->expiredItem = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEproUploadToQuoteEnable','isD190723FixToggleEnable'])
            ->getMock();

        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods([
                'getNewDocumentsApiImagePreviewToggle'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteDetailsViewModelData = $objectManagerHelper->getObject(
            QuoteDetailsViewModel::class,
            [
                'quote' => $this->quote,
                'quoteFactory' => $this->quoteFactory,
                'request' => $this->request,
                'adminConfigHelper' => $this->adminConfigHelper,
                'customerSession' => $this->customerSession,
                'responseFactory' => $this->responseFactory,
                'response' => $this->response,
                'serializer' => $this->serializerMock,
                'fxoRateQuote' => $this->fxoRateQuoteHelper,
                'storeManager' => $this->storeManager,
                'graphqlApiHelper' => $this->graphqlApiHelper,
                'timezone' => $this->timezone,
                'productInfo' => $this->productInfo,
                'expiredItem' => $this->expiredItem,
                'config' => $this->config,
                'configProvider' => $this->configProvider,
                'fxocmhelper' => $this->dataHelperMock,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     * Test getQuote
     *
     * @return void
     */
    public function testGetQuote()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(1234245);
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('load')->willReturnSelf();
        $this->quote->expects($this->any())->method('getId')->willReturn(1);

        $this->adminConfigHelper->expects($this->once())->method('isToggleD206707Enabled')->willReturn(false);

        $this->assertIsObject($this->quoteDetailsViewModelData->getQuote());
    }

    /**
     * Test getQuote with redirect
     *
     * @return void
     */
    public function testGetQuoteWithRedirect()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(1234245);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('load')->willReturnSelf();
        $this->responseFactory->expects($this->any())->method('create')->willReturn($this->response);
        $this->response->expects($this->any())->method('setRedirect')->willReturnSelf();
        $this->response->expects($this->any())->method('sendResponse')->willReturn('https://shop.fedex.com');

        $this->adminConfigHelper->expects($this->once())->method('isToggleD206707Enabled')->willReturn(false);

        $this->assertIsObject($this->quoteDetailsViewModelData->getQuote());
    }

    /**
     * Test getQuoteBySessionQuoteId
     *
     * @return void
     */
    public function testGetQuoteBySessionQuoteId()
    {
        $this->customerSession->expects($this->once())->method('getUploadToQuoteId')->willReturn(876545);
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('load')->willReturn(true);

        $this->assertEquals(true, $this->quoteDetailsViewModelData->getQuoteBySessionQuoteId());
    }

    /**
     * Test getFormattedPhone
     *
     * @return void
     */
    public function testGetFormattedPhone()
    {
        $phoneNumber = '8885556666';
        $returnValue = '(888) 555-6666';

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getFormattedPhone($phoneNumber));
    }

    /**
     * Test getExpiryDate
     *
     * @return void
     */
    public function testGetExpiryDate()
    {
        $date = '2023-10-30 05:59:50';
        $returnValue = '2023-11-28 05:59:50';
        $this->adminConfigHelper->expects($this->once())->method('getExpiryDate')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getExpiryDate($date, 'd/m/y'));
    }

    /**
     * Test convertPrice
     *
     * @return void
     */
    public function testConvertPrice()
    {
        $price = '0.6100';
        $returnValue = '$0.61';
        $this->adminConfigHelper->expects($this->once())->method('convertPrice')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->convertPrice($price));
    }

    /**
     * Test getFormattedDate
     *
     * @return void
     */
    public function testGetFormattedDate()
    {
        $date = '2023-10-29 05:59:50';
        $returnValue = 'November 2, 2023';
        $this->adminConfigHelper->expects($this->once())->method('getFormattedDate')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getFormattedDate($date, 'F j, Y'));
    }

    /**
     * Test getStatusLabelByStatusKey
     *
     * @return void
     */
    public function testGetStatusLabelByStatusKey()
    {
        $key = 'created';
        $returnValue = 'Store Review';
        $this->adminConfigHelper->expects($this->once())->method('getQuoteStatusLabel')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getStatusLabelByStatusKey($key));
    }

    /**
     * Test getStatusLabelByQuoteId
     *
     * @return void
     */
    public function testGetStatusLabelByQuoteId()
    {
        $quoteId = '123453';
        $returnValue = 'Store Review';
        $this->adminConfigHelper->expects($this->once())->method('getNegotiableQuoteStatus')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getStatusLabelByQuoteId($quoteId));
    }

    /**
     * Test checkIsPunchoutQuote
     *
     * @return void
     */
    public function testCheckIsPunchoutQuote()
    {
        $quoteId = '123453';
        $returnValue = true;
        $this->adminConfigHelper->expects($this->once())
            ->method('checkIsPunchoutQuote')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->checkIsPunchoutQuote($quoteId));
    }

    /**
     * Test getQuoteStatusKeyByQuoteId
     *
     * @return void
     */
    public function testGetQuoteStatusKeyByQuoteId()
    {
        $quoteId = '123453';
        $returnValue = 'created';
        $this->adminConfigHelper->expects($this->once())
            ->method('getQuoteStatusKeyByQuoteId')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getQuoteStatusKeyByQuoteId($quoteId));
    }

    /**
     * Test getQuoteDeclinedModalTitle
     *
     * @return void
     */
    public function testGetQuoteDeclinedModalTitle()
    {
        $returnValue = 'Title';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getQuoteDeclinedModalTitle());
    }

    /**
     * Test getQuoteDeclinedMessage
     *
     * @return void
     */
    public function testGetQuoteDeclinedMessage()
    {
        $returnValue = 'Message';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getQuoteDeclinedMessage());
    }

    /**
     * Test getQuoteDeclinedReason
     *
     * @return void
     */
    public function testGetQuoteDeclinedReason()
    {
        $returnValue = [['number_field' => 1, 'text_field' => 'Test']];
        $this->adminConfigHelper
            ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getQuoteDeclinedReason());
    }

    /**
     * Test method for getRequestChangeMessage
     *
     * @return void
     */
    public function testGetRequestChangeMessage()
    {
        $returnValue = 'Message';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getRequestChangeMessage());
    }

    /**
     * Test method for getRequestChangeCancelCTALabel
     *
     * @return void
     */
    public function testGetRequestChangeCancelCTALabel()
    {
        $returnValue = 'Cancel';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getRequestChangeCancelCTALabel());
    }

    /**
     * Test method for getRequestChangeCTALabel
     *
     * @return void
     */
    public function testGetRequestChangeCTALabel()
    {
        $returnValue = 'Request Change';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getRequestChangeCTALabel());
    }

    /**
     * Test method for getRateQuoteResponse
     *
     * @return void
     */
    public function testGetRateQuoteResponse()
    {
        $fxoRate = ['output' => [
            'alerts' => [
                [
                    'code' => 'QCXS.SERVICE.ZERODOLLARSKU',
                    'message' => "Pricing response has zero dollar sku",
                    'alertType' => "WARNING"
                ]
            ],
        ]];
        $formData = [
            'quote_id' => 410265,
            'quote_item_id' => 132790,
            'print_instructions' => 'test'
        ];

        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'properties' => [
                        [
                            'name' => 'USER_SPECIAL_INSTRUCTIONS',
                            'value' => 'Test'
                        ]
                    ]
                ],
            ],
        ];

        $this->quoteFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->fxoRateQuoteHelper->expects($this->any())->method('getFXORateQuote')->willReturn($fxoRate);
        $this->quoteFactory->expects($this->once())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getItemId')->willReturn(132790);
        $this->adminConfigHelper->expects($this->once())->method('updateQuoteStatusByKey')->willReturn(true);
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->serializerMock->expects($this->any())->method('serialize')->willReturn('test string');
        $this->option->expects($this->any())->method('setvalue')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();

        $this->adminConfigHelper->expects($this->once())->method('isToggleD206707Enabled')->willReturn(false);

        $this->assertEquals(true, $this->quoteDetailsViewModelData->getRateQuoteResponse($formData));
    }

    /**
     * Test method for getRateQuoteResponse with false
     *
     * @return void
     */
    public function testGetRateQuoteResponseWithFalse()
    {
        $formData = [
            'quote_id' => 0,
            'quote_item_id' => 132790,
            'print_instructions' => 'test'
        ];

        $this->assertEquals(false, $this->quoteDetailsViewModelData->getRateQuoteResponse($formData));
    }

    /**
     * Test getPercentBasedOnStatus
     *
     * @return void
     */
    public function testGetPercentBasedOnStatusOrdered()
    {
        $this->adminConfigHelper->method('getQuoteStatusKeyByQuoteId')
        ->willReturn(NegotiableQuoteInterface::STATUS_ORDERED);
        $result = $this->quoteDetailsViewModelData->getPercentBasedOnStatus(123);

        $this->assertSame(100, $result['percent']);
        $this->assertSame('quote-status-closed-and-approve', $result['class']);
        $this->assertTrue($result['showicon']);
    }

    /**
     * Test getPercentBasedOnStatusCreated
     *
     * @return void
     */
    public function testGetPercentBasedOnStatusDeclined()
    {
        $this->adminConfigHelper->method('getQuoteStatusKeyByQuoteId')
        ->willReturn(NegotiableQuoteInterface::STATUS_DECLINED);
        $result = $this->quoteDetailsViewModelData->getPercentBasedOnStatus(123);

        $this->assertSame(100, $result['percent']);
        $this->assertSame('quote-status-expired-and-declined', $result['class']);
        $this->assertFalse($result['showicon']);
    }

    /**
     * Test getPercentBasedOnStatusDeclined
     *
     * @return void
     */
    public function testGetPercentBasedOnStatusCreated()
    {
        $this->adminConfigHelper->method('getQuoteStatusKeyByQuoteId')
        ->willReturn(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN);
        $result = $this->quoteDetailsViewModelData->getPercentBasedOnStatus(123);

        $this->assertSame(40, $result['percent']);
        $this->assertSame('quote-status-created', $result['class']);
        $this->assertFalse($result['showicon']);
    }

    /**
     * Test getPercentBasedOnStatusByCustomer
     *
     * @return void
     */
    public function testGetPercentBasedOnStatusSubmittedByCustomer()
    {
        $this->adminConfigHelper->method('getQuoteStatusKeyByQuoteId')
        ->willReturn(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER);
        $result = $this->quoteDetailsViewModelData->getPercentBasedOnStatus(123);

        $this->assertSame(62, $result['percent']);
        $this->assertSame('quote-status-submitted-customer', $result['class']);
        $this->assertFalse($result['showicon']);
    }

    /**
     * Test getPercentBasedOnStatusSubmittedByAdmin
     *
     * @return void
     */
    public function testGetPercentBasedOnStatusSubmittdByAdmin()
    {
        $this->adminConfigHelper->method('getQuoteStatusKeyByQuoteId')
        ->willReturn(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        $result = $this->quoteDetailsViewModelData->getPercentBasedOnStatus(123);

        $this->assertSame(82, $result['percent']);
        $this->assertSame('quote-status-submitted-admin', $result['class']);
        $this->assertFalse($result['showicon']);
    }

    /**
     * Test getNonStandardImageUrl
     *
     * @return void
     */
    public function testGetNonStandardImageUrl()
    {
        $mediaUrl = 'https://staging3.fedex.com/';
        $imageUrl = $mediaUrl.'images/upload-to-quote/nostandard-image.png';
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getBaseUrl')->willReturn($mediaUrl);

        $this->assertNUll($this->quoteDetailsViewModelData->getNonStandardImageUrl());
    }

    /**
     * Test getDeletedItems
     *
     * @return void
     */
    public function testGetDeletedItems()
    {
        $deletedItems = [1342, 3421];
        $this->adminConfigHelper->expects($this->once())->method('getDeletedItems')->willReturn($deletedItems);

        $this->assertEquals($deletedItems, $this->quoteDetailsViewModelData->getDeletedItems());
    }

    /**
     * Test getTotalPriceInfForRemainingItem
     *
     * @return void
     */
    public function testGetTotalPriceInfForRemainingItem()
    {
        $totalAmount = [
            'totalAmount' => 24.45
        ];
        $this->adminConfigHelper->expects($this->once())
        ->method('getTotalPriceInfForRemainingItem')->willReturn($totalAmount);

        $this->assertEquals($totalAmount, $this->quoteDetailsViewModelData->getTotalPriceInfForRemainingItem());
    }

    /**
     * Test getMostRecentQuoteNote
     *
     * @return void
     */
    public function testGetMostRecentQuoteNote()
    {
        $quoteId = 123;
        $mockedNotes = [
            [
                'date' => '2024-02-01 08:30:00',
                'comment' => 'First comment',
                'created_by' => 'CUSTOMER',
            ],
            [
                'date' => '2024-02-02 09:45:00',
                'comment' => 'Second comment',
                'created_by' => 'ADMIN',
            ],
            [
                'date' => '2024-02-03 10:15:00',
                'comment' => 'Third comment',
                'created_by' => 'CUSTOMER',
            ],
        ];
        $this->graphqlApiHelper->expects($this->once())->method('getQuoteNotes')->
        with($quoteId)->willReturn($mockedNotes);
        $result = $this->quoteDetailsViewModelData->getMostRecentQuoteNote($quoteId);
        $expectedResult = [
            'date' => '2024-02-03 10:15:00',
            'comment' => 'Third comment',
            'created_by' => 'CUSTOMER',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test getMostRecentQuoteNoteWithEmptyNotes
     *
     * @return void
     */
    public function testGetMostRecentQuoteNoteWithEmptyNotes()
    {
        $quoteId = 123;
        $mockedNotes = [];
        $this->graphqlApiHelper->expects($this->once())->method('getQuoteNotes')->
        with($quoteId)->willReturn($mockedNotes);
        $this->graphqlApiHelper->expects($this->once())->method('getQuoteNotes')->
        with($quoteId)->willReturn($mockedNotes);
        $result = $this->quoteDetailsViewModelData->getMostRecentQuoteNote($quoteId);

        $this->assertEquals([], $result);
    }

    /**
     * Test getStatusDataByQuoteId
     *
     * @return void
     */
    public function testGetStatusDataByQuoteId()
    {
        $quoteId = 123;
        $this->adminConfigHelper->expects($this->once())->method('getStatusDataByQuoteId')->willReturn($quoteId);

        $this->assertEquals($quoteId, $this->quoteDetailsViewModelData->getStatusDataByQuoteId($quoteId));
    }

    /**
     * Test getProductValue
     *
     * @return void
     */
    public function testGetProductValue()
    {
        $returnValue = true;
        $this->adminConfigHelper->expects($this->once())
        ->method('getProductValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getProductValue(123, 342));
    }

    /**
     * Test getProductJson
     *
     * @return void
     */
    public function testGetProductJson()
    {
        $returnValue = true;
        $this->adminConfigHelper->expects($this->once())
        ->method('getProductJson')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getProductJson(123, 342));
    }

    /**
     * Test isU2QCustomerSIEnabled
     *
     * @return void
     */
    public function testisU2QCustomerSIEnabled()
    {
        $this->adminConfigHelper->expects($this->once())->method('isU2QCustomerSIEnabled')->willReturn(true);

        $this->assertTrue($this->quoteDetailsViewModelData->isU2QCustomerSIEnabled());
    }

    /**
     * Test getProductAttributeName
     *
     * @return void
     */
    public function testGetProductAttributeName()
    {
        $returnValue = 'Test';
        $this->adminConfigHelper->expects($this->once())->method('getProductAttributeName')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->getProductAttributeName(123));
    }

    /**
     * Test productImageUrl
     *
     * @return void
     */
    public function testProductImageUrl()
    {
        $returnValue = 'https://test.png';
        $this->adminConfigHelper->expects($this->once())->method('productImageUrl')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsViewModelData->productImageUrl('test', 70, 60));
    }

    /**
     * Test isEproUploadToQuoteEnable
     *
     * @return void
     */
    public function testisEproUploadToQuoteEnable()
    {
        $this->config->expects($this->once())->method('isEproUploadToQuoteEnable')->willReturn(true);

        $this->assertTrue($this->quoteDetailsViewModelData->isEproUploadToQuoteEnable());
    }

    /**
     * Test New Documents Api Image Enable Toggle.
     */
    public function testisNewDocumentsApiImageEnableTrue()
    {
        $this->dataHelperMock->method('getNewDocumentsApiImagePreviewToggle')
            ->willReturn(true);

        $result = $this->quoteDetailsViewModelData->isNewDocumentsApiImageEnable();
        $this->assertTrue($result);
    }

    /**
     * Test isD190723FixToggleEnable
     */
    public function testisD190723FixToggleEnable()
    {
        $this->config->expects($this->any())->method('isD190723FixToggleEnable')->willReturn(true);

        $this->assertFalse($this->quoteDetailsViewModelData->isEproUploadToQuoteEnable());
    }

    /**
     * Test isFuseBidToggleEnabled
     *
     * @return void
     */
    public function testIsFuseBidToggleEnabled()
    {
        $this->fuseBidViewModel->expects($this->once())->method('isFuseBidToggleEnabled')->willReturn(true);

        $this->assertTrue($this->quoteDetailsViewModelData->isFuseBidToggleEnabled());
    }

    /**
     * @return void
     */
    public function testGetIsFclCustomer()
    {
        $ssoConfiguration = $this->getMockBuilder(\Fedex\SSO\ViewModel\SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFclCustomer'])
            ->getMock();
        $ssoConfiguration->expects($this->once())->method('isFclCustomer')->willReturn(true);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $viewModel = $objectManagerHelper->getObject(
            \Fedex\UploadToQuote\ViewModel\QuoteDetailsViewModel::class,
            [
                // ...other dependencies...
                'ssoConfiguration' => $ssoConfiguration
            ]
        );

        $this->assertTrue($viewModel->getIsFclCustomer());
    }
}
