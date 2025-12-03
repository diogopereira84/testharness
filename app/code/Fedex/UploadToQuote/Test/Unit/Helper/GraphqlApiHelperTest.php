<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory;
use Magento\NegotiableQuote\Model\Comment;
use Magento\NegotiableQuote\Model\CommentRepository;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Mars\Helper\PublishToQueue;
use Fedex\Mars\Model\Config as MarsConfig;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

class GraphqlApiHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $companyRepositoryMock;
    protected $customerRepositoryMock;
    protected $customerInterfaceMock;
    protected $customerExtensionInterfaceMock;
    protected $companyCustomerInterfaceMock;
    protected $companyInterfaceMock;
    protected $quoteMock;
    protected $itemMock;
    protected $itemFactory;
    protected $commentList;
    protected $comment;
    protected $negotiableQuote;
    protected $graphqlApiHelperMock;
    /** Customer Id test value */
    private const CUSTOMER_ID = 1;

    /** Company id for test  */
    private const COMPANY_ID = 6;

    /** Company name for test*/
    private const COMPANY_NAME = 'walmart';

    /** Company name for test*/
    private const COMMENT_CREATION_DATE = '2023-08-10';

    /** @var CommentRepositoryInterface|MockObject */
    protected $companyRepoMock;

    /** @var CustomerRepositoryInterface|MockObject */
    protected $customerRepoMock;

    /** @var cartDataHelperMock|MockObject */
    protected $cartDataHelperMock;

    /** @var SearchCriteriaBuilder|MockObject */
    protected $searchCriteriaBuilderMock;

    /** @var CompanyRepositoryInterface|MockObject */
    protected $commentRepoMock;

    /** @var CommentInterfaceFactory|MockObject */
    protected $commentFactoryMock;

    /** @var TimezoneInterface $timezoneInterface */
    protected $timezoneInterface;

    /** @var AdminConfigHelper|MockObject */
    private $adminConfigHelperMock;

    /** @var NegotiableQuoteRepositoryInterface|MockObject */
     private $negotiableQuoteRepository;

    /**  @var History|MockObject */
    private $quoteHistoryMock;

    /** @var ScopeConfigInterface $scopeConfigMock */
    protected $scopeConfigMock;

    /** @var FXORateQuote $fxoRateQuote */
    protected $fxoRateQuote;

    /**
     * @var PublishToQueue
     */
    private PublishToQueue $publishMock;

    /**
     * @var MarsConfig
     */
    private MarsConfig $marsConfigMock;

    /**
     * @var QuoteFactory|MockObject
     */
    protected $quoteFactory;

    /**
     * @var SessionManagerInterface|MockObject
     */
    protected $sessionMock;

    /**
     * @var OrderFactory|MockObject
     */
    protected $order;

    /**
     * @var Order|MockObject
     */
    protected $orderMock;

    /**
     * @var Item|MockObject
     */
    protected $item;

     /**
      * @var LoggerInterface|MockObject
      */
    protected $loggerMock;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    protected $cartIntegrationRepositoryMock;

     /** @var FuseBidViewModel|MockObject */
     private $fuseBidViewModelMock;

    /**
     * @var ToggleConfig|(ToggleConfig&object&\PHPUnit\Framework\MockObject\MockObject)|(ToggleConfig&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
     private $toggleConfigMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create'])
            ->getMockForAbstractClass();

        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['decryptData'])
            ->getMockForAbstractClass();

        $this->commentRepoMock = $this->getMockBuilder(CommentRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'save', 'getList', 'getItems'])
            ->getMockForAbstractClass();

        $this->commentFactoryMock = $this->getMockBuilder(CommentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerExtensionInterfaceMock = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $this->companyCustomerInterfaceMock = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId'])
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getCompanyName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(OrderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['loadByIncrementId', 'getData', 'getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->item = $this->getMockBuilder(Item::class)
            ->setMethods(['getProductOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getData',
                    'getCustomerFirstname',
                    'getCustomerLastname',
                    'getCustomerEmail',
                    'getCustomerTelephone',
                    'getCreatedAt',
                    'getUpdatedAt',
                    'getConvertedAt',
                    'getId',
                    'getAllVisibleItems',
                    'getFedexAccountNumber',
                    'getCustomerId',
                    'getExtensionAttributes',
                    'getNegotiableQuote',
                    'load'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->setMethods(
                [
                    'getOptionByCode',
                    'getValue',
                    'getId',
                    'getName',
                    'getQty',
                    'getPrice',
                    'getDiscountAmount',
                    'getBasePrice',
                    'getRowTotal',
                ]
            )
            ->disableOriginalconstructor()
            ->getMock();

        $this->commentList = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->comment = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->setMethods([
                'getExpiryDate',
                'addCustomLog',
                'updateGridQuoteStatus',
                'toggleUploadToQuoteSubmitDate',
                'isToggleB2564807Enabled',
                'getSubmitDate'
            ])

            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->setMethods(['getStatus', 'setStatus','getData', 'getExpirationPeriod','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteHistoryMock = $this->getMockBuilder(History::class)
            ->setMethods(['updateStatusLog'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->fxoRateQuote = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();

        $this->publishMock = $this->getMockBuilder(PublishToQueue::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMock();

        $this->marsConfigMock = $this->getMockBuilder(MarsConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEnabled'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sessionMock =  $this->getMockBuilder(SessionManagerInterface::class)
             ->setMethods(['getSessionId'])
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
             ->setMethods(['Info'])
             ->disableOriginalConstructor()
              ->getMockForAbstractClass();

        $this->cartIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
              ->setMethods(['getByQuoteId'])
              ->disableOriginalConstructor()
               ->getMockForAbstractClass();

        $this->fuseBidViewModelMock = $this->getMockBuilder(FuseBidViewModel::class)
               ->disableOriginalConstructor()
               ->setMethods(['isFuseBidToggleEnabled'])
               ->getMock();

        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->graphqlApiHelperMock = $objectManagerHelper->getObject(
            GraphqlApiHelper::class,
            [
                'context' => $this->contextMock,
                'commentRepository' => $this->commentRepoMock,
                'commentFactory' => $this->commentFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'companyRepository' => $this->companyRepositoryMock,
                'cartDataHelper' => $this->cartDataHelperMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'timezoneInterface' => $this->timezoneInterface,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'negotiableQuoteRepository'  => $this->negotiableQuoteRepository,
                'quoteHistory' => $this->quoteHistoryMock,
                'scopeConfig' => $this->scopeConfigMock,
                'fxoRateQuote' => $this->fxoRateQuote,
                'publish' => $this->publishMock,
                'marsConfig' => $this->marsConfigMock,
                'session' => $this->sessionMock,
                'logger' => $this->loggerMock,
                'quoteFactory' => $this->quoteFactory,
                'cartIntegrationRepository' => $this->cartIntegrationRepositoryMock,
                'FuseBidViewModel' => $this->fuseBidViewModelMock,
                'order' => $this->order,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }

    /**
     * Test method to get contact info
     *
     * @return void
     */
    public function testgetQuoteContactInfo()
    {
        $this->quoteMock->method('getCustomerEmail')->willReturn('test@example.com');
        $this->quoteMock->method('getCustomerTelephone')->willReturn('123456789');
        $this->quoteMock->method('getCustomerFirstname')->willReturn('John');
        $this->quoteMock->method('getCustomerLastname')->willReturn('Doe');
        $this->fuseBidViewModelMock->expects($this->any())->method('isFuseBidToggleEnabled')
        ->willReturn(true);
        $result = $this->graphqlApiHelperMock->getQuoteContactInfo($this->quoteMock);

        $this->assertEquals('test@example.com', $result['email']);

        $this->assertEquals('123456789', $result['phone_number']);

        $this->assertEquals('John', $result['first_name']);

        $this->assertEquals('Doe', $result['last_name']);
    }

    /**
     * Test method to check dummy contact info
     *
     * @return void
     */
    public function testgetQuoteContactDummyInfo()
    {
        $this->quoteMock->method('getCustomerEmail')->willReturn('dummy.customer+673f0f6ab2fb4@dummy.com');
        $this->quoteMock->method('getCustomerTelephone')->willReturn('123456789');
        $this->quoteMock->method('getCustomerFirstname')->willReturn('dummy');
        $this->quoteMock->method('getCustomerLastname')->willReturn('Customer');
        $this->fuseBidViewModelMock->expects($this->any())->method('isFuseBidToggleEnabled')
        ->willReturn(true);
         $this->graphqlApiHelperMock->getQuoteContactInfo($this->quoteMock);
    }

     /**
      * Data provider for quote info tests
      */
    public function quoteInfoProvider()
    {
        return [
            // Scenario 1: UpdatedAt is not null
            [
                'updatedAt' => self::COMMENT_CREATION_DATE,
                'expectedStatus' => 'CREATED',
                'expectedId' => '123',
            ],
            // Scenario 2: UpdatedAt is null
            [
                'updatedAt' => null,
                'expectedStatus' => 'CREATED',
                'expectedId' => '123',
            ],
        ];
    }

    /**
     * Test method to get quote info
     *
     * @dataProvider quoteInfoProvider
     * @param mixed $updatedAt
     * @param string $expectedStatus
     * @param string $expectedId
     * @return void
     */
    public function testGetQuoteInfo($updatedAt, $expectedStatus, $expectedId)
    {
        $this->quoteMock->method('getId')->willReturn('123');
        $this->quoteMock->expects($this->any())->method('getCreatedAt')->willReturn(self::COMMENT_CREATION_DATE);
        $this->quoteMock->expects($this->any())->method('getUpdatedAt')->willReturn($updatedAt);
        $this->quoteMock->expects($this->any())->method('getConvertedAt')->willReturn($updatedAt);
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($this->negotiableQuote);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->adminConfigHelperMock->expects($this->any())
            ->method('isToggleB2564807Enabled')
            ->willReturn(1);
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn("created");
        $this->negotiableQuote->expects($this->any())->method('getData')
        ->willReturn(['negotiated_total_price' => '343']);
        $this->negotiableQuote->expects($this->any())->method('getData')
        ->willReturn(['quote_mgnt_location_code' => '1234']);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format');

        $result = $this->graphqlApiHelperMock->getQuoteInfo($this->quoteMock);

        $this->assertEquals($expectedStatus, $result['quote_status']);
        $this->assertEquals($expectedId, $result['quote_id']);
    }

    /**
     * Test method to get contact info
     *
     * @return void
     */
    public function testgetQuoteLineItems()
    {
        $rateRespone =  [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    "rateQuoteDetails" => [
                        [
                            "productLines" => [
                                [
                                    "instanceId" => "140538",
                                    "priceable" => true,
                                ],
                                [
                                    "instanceId" => "140538",
                                    "priceable" => false,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->itemMock->method('getOptionByCode')->willReturnSelf();
        $this->itemMock->method('getValue')
        ->willReturn('{"external_prod": [{"key": "value"}],"originalFiles": [{"key": "value"}]}');
        $this->itemMock->method('getId')->willReturn(1);
        $this->itemMock->method('getName')->willReturn('Product Name');
        $this->itemMock->method('getQty')->willReturn(2);
        $this->itemMock->method('getPrice')->willReturn(19.99);
        $this->itemMock->method('getDiscountAmount')->willReturn(5.00);
        $this->itemMock->method('getBasePrice')->willReturn(24.99);
        $this->itemMock->method('getRowTotal')->willReturn(39.98);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(GraphqlApiHelper::TIGER_D236536)
            ->willReturn(false);

        $result = $this->graphqlApiHelperMock->getQuoteLineItems($this->quoteMock, $rateRespone);

        $this->assertArrayHasKey('0', $result);
    }

    /**
     * Test method to get contact info
     *
     * @return void
     */
    public function testgetQuoteLineItemsForEmptyOriginalFiles()
    {
        $rateRespone =  [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    "rateQuoteDetails" => [
                        [
                            "productLines" => [
                                [
                                    "instanceId" => "140538",
                                    "priceable" => true,
                                ],
                                [
                                    "instanceId" => "140538",
                                    "priceable" => false,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->itemMock->method('getOptionByCode')->willReturnSelf();
        $this->itemMock->method('getValue')->willReturn('{"external_prod": [{"key": "value"}]}');
        $this->itemMock->method('getId')->willReturn(1);
        $this->itemMock->method('getName')->willReturn('Product Name');
        $this->itemMock->method('getQty')->willReturn(2);
        $this->itemMock->method('getPrice')->willReturn(19.99);
        $this->itemMock->method('getDiscountAmount')->willReturn(5.00);
        $this->itemMock->method('getBasePrice')->willReturn(24.99);
        $this->itemMock->method('getRowTotal')->willReturn(39.98);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(GraphqlApiHelper::TIGER_D236536)
            ->willReturn(false);
        $result = $this->graphqlApiHelperMock->getQuoteLineItems($this->quoteMock, $rateRespone);

        $this->assertArrayHasKey('0', $result);
    }

    /**
     * Test method to get Fxo Account Number
     * @return void
     */
    public function testGetFxoAccountNumberOfQuote()
    {
        $this->quoteMock->method('getFedexAccountNumber')->willReturn('encrypted_account_number');
        $this->cartDataHelperMock->expects($this->once())
            ->method('decryptData')
            ->with('encrypted_account_number')
            ->willReturn('decrypted_account_number');

        $result = $this->graphqlApiHelperMock->getFxoAccountNumberOfQuote($this->quoteMock);

        $this->assertEquals('decrypted_account_number', $result);
    }

    /**
     * Test method to get company name
     *
     * @return void
     */
    public function testGetQuoteCompanyName()
    {
        $this->quoteMock->method('getCustomerId')->willReturn(self::CUSTOMER_ID);
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);
        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);
        $this->companyRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getCompanyName')
            ->willReturn(self::COMPANY_NAME);

        $result = $this->graphqlApiHelperMock->getQuoteCompanyName($this->quoteMock);

        $this->assertEquals(self::COMPANY_NAME, $result);
    }

    /**
     * Test method to get company name with exception
     *
     * @return void
     */
    public function testGetQuoteCompanyNameException()
    {
        $exceptionMessage = 'Exception message';
        $this->quoteMock->method('getCustomerId')->willReturn(self::CUSTOMER_ID);
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willThrowException(new \Exception($exceptionMessage));
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->graphqlApiHelperMock->getQuoteCompanyName($this->quoteMock);
    }

    /**
     * Test method to set quote notes
     *
     * @return void
     */
    public function testSetQuoteNotes()
    {
        $commentText = 'Test comment';
        $quoteId = 1;
        $type = "note_added";
        $this->commentFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->comment);
        $this->comment->expects($this->once())
            ->method('setParentId')
            ->with($quoteId);
        $this->comment->expects($this->once())
            ->method('setComment')
            ->with($commentText);
        $this->comment->expects($this->any())
            ->method('setCreatorType')
            ->with('2');
        $this->comment->expects($this->once())
            ->method('setCreatorId')
            ->with('2');
        $this->comment->expects($this->once())
            ->method('save');
        $this->commentRepoMock->expects($this->never())
            ->method('save');
        $this->marsConfigMock->expects($this->once())
            ->method('isEnabled')->willReturn(true);
        $this->publishMock->expects($this->once())
            ->method('publish')->willReturnSelf();

        $this->graphqlApiHelperMock->setQuoteNotes($commentText, $quoteId, $type);
    }

    /**
     * Test method to set quote notes for diff creator type
     *
     * @return void
     */
    public function testSetQuoteNotesForApproved()
    {
        $commentText = 'Test comment';
        $quoteId = 1;
        $type = "quote_approved";
        $this->commentFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->comment);
        $this->comment->expects($this->once())
            ->method('setParentId')
            ->with($quoteId);
        $this->comment->expects($this->once())
            ->method('setComment')
            ->with($commentText);
        $this->comment->expects($this->any())
            ->method('setCreatorType')
            ->with('3');
        $this->comment->expects($this->once())
            ->method('setCreatorId')
            ->with('2');
        $this->comment->expects($this->once())
            ->method('save');
        $this->commentRepoMock->expects($this->never())
            ->method('save');
        $this->marsConfigMock->expects($this->once())
            ->method('isEnabled')->willReturn(true);
        $this->publishMock->expects($this->once())
            ->method('publish')->willReturnSelf();

        $this->graphqlApiHelperMock->setQuoteNotes($commentText, $quoteId, $type);
    }

    /**
     * Test method to set quote notes with exception
     *
     * @return void
     */
    public function testSetQuoteNotesException()
    {
        $commentText = 'Test comment';
        $quoteId = 1;
        $type = "note_added";
        $exceptionMessage = 'Exception message';
        $this->commentFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->comment);
        $this->comment->expects($this->once())
            ->method('setParentId')
            ->willThrowException(new \Exception($exceptionMessage));
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->graphqlApiHelperMock->setQuoteNotes($commentText, $quoteId, $type);
    }

    /**
     * Test method to get quote notes
     *
     * @return void
     */
    public function testGetQuoteNotes()
    {
        $quoteId = 1;
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->commentList->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->comment]);
        $this->commentList->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->comment]);
        $this->commentRepoMock->expects($this->once())
            ->method('getList')
            ->willReturn($this->commentList);
        $this->comment
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn(self::COMMENT_CREATION_DATE);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
        ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));

        $result = $this->graphqlApiHelperMock->getQuoteNotes($quoteId);

        $this->assertArrayHasKey('0', $result);
    }

    /**
     * Test method to change quote status
     *
     * @param string $status
     * @param array $expectedValues
     * @return void
     */
    private function assertChangeQuoteStatus($status, $expectedValues)
    {
        $quoteId = 123;
        $this->quoteMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->quoteMock->getExtensionAttributes()
        ->expects($this->once())
        ->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->once())
        ->method('setStatus')->with($status);
        $this->negotiableQuote->expects($this->once())
        ->method('save');
        $this->quoteMock->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getId')->willReturn($quoteId);
        $this->negotiableQuote->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->quoteHistoryMock->expects($this->once())->method('updateStatusLog')->with($quoteId, true);
        $this->timezoneInterface->expects($this->exactly(2))->method('date')->willReturn($this->timezoneInterface);
        $this->timezoneInterface->expects($this->exactly(2))->method('format')->willReturn('2024-02-23 00:00:00');
        $this->adminConfigHelperMock->expects($this->any())->method('addCustomLog')->with($quoteId, $expectedValues);

        $this->graphqlApiHelperMock->changeQuoteStatus($this->quoteMock, $status);
    }

    /**
     * Test method to change quote status for 'submitted_by_admin' status
     *
     * @return void
     */
    public function testChangeQuoteStatusForSubmittedByAdmin()
    {
        $status = 'submitted_by_admin';
        $expectedValues[] = [
            'quoteStatus' => $status,
            'readyForReviewDate' => "2024-02-23 00:00:00",
            'readyForReviewTime' => "2024-02-23 00:00:00"
        ];
        $this->assertChangeQuoteStatus($status, $expectedValues);
    }

    /**
     * Test method to change quote status for 'closed' status
     *
     * @return void
     */
    public function testChangeQuoteStatusForClosedStatus()
    {
        $status = 'closed';
        $expectedValues[] = [
            'quoteStatus' => $status,
            'closedDate' => "2024-02-23 00:00:00",
            'closedTime' => "2024-02-23 00:00:00"
        ];
        $this->assertChangeQuoteStatus($status, $expectedValues);
    }

    /**
     * Test method to  Get Fuse AddToQuote Product
     *
     * @return void
     */
    public function testgetFuseAddToQuoteProduct()
    {
        $productId = 2;
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($productId);

        $this->assertEquals($productId, $this->graphqlApiHelperMock->getFuseAddToQuoteProduct());
    }

    /**
     * Test method to  Get Fuse AddToQuote Product
     *
     * @return void
     */
    public function testquotesavebeforeratequoteFixToggle()
    {
        $value = 1;
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($value);

        $this->assertEquals($value, $this->graphqlApiHelperMock->quotesavebeforeratequoteFixToggle());
    }

    /**
     * Test method to test GetRateSummaryData
     *
     * @return void
     */
    public function testGetRateSummaryData()
    {
        $rateResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'grossAmount' => 100,
                            'discounts' => [],
                            'totalDiscountAmount' => 0,
                            'netAmount' => 100,
                            'taxableAmount' => 100,
                            'taxAmount' => 10,
                            'totalAmount' => 110,
                            'totalFees' => 0,
                            'productsTotalAmount' => 100,
                            'deliveriesTotalAmount' => 0,
                            'estimatedVsActual' => 'estimated'
                        ]
                    ]
                ]
            ]
        ];

        $expectedResult = [
            'grossAmount' => 100,
            'discounts' => [],
            'totalDiscountAmount' => 0,
            'netAmount' => 100,
            'taxableAmount' => 100,
            'taxAmount' => 10,
            'totalAmount' => 110,
            'totalFees' => 0,
            'productsTotalAmount' => 100,
            'deliveriesTotalAmount' => 0,
            'estimatedVsActual' => 'estimated'
        ];

        $this->assertEquals($expectedResult, $this->graphqlApiHelperMock->getRateSummaryData($rateResponse));
    }

    /**
     * Test method to test GetRateSummaryData
     *
     * @return void
     */
    public function testGetLineItemRateDetails()
    {
        $rateResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                [
                                    'instanceId' => 1,
                                    'productId' => 123,
                                    'type' => 'type1',
                                    'name' => 'Product1',
                                    'userProductName' => 'UserProduct1',
                                    'unitQuantity' => 1,
                                    'unitOfMeasurement' => 'unit',
                                    'priceable' => true,
                                    'productRetailPrice' => 50,
                                    'productLinePrice' => 50,
                                    'productDiscountAmount' => 0,
                                    'productLineDiscounts' => [],
                                    'productLineDetails' => [],
                                    'links' => [],
                                    'specialInstructions' => '',
                                    'reorderCatalogReference' => '',
                                    'lineReorderEligibility' => true,
                                    'vendorReference' => '',
                                    'productTaxAmount' => 5
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $itemId = 1;
        $expectedResult = [
            'instanceId' => 1,
            'productId' => 123,
            'type' => 'type1',
            'name' => 'Product1',
            'userProductName' => 'UserProduct1',
            'unitQuantity' => 1,
            'unitOfMeasurement' => 'unit',
            'priceable' => true,
            'productRetailPrice' => 50,
            'productLinePrice' => 50,
            'productDiscountAmount' => 0,
            'productLineDiscounts' => [],
            'productLineDetails' => [],
            'links' => [],
            'specialInstructions' => '',
            'reorderCatalogReference' => '',
            'lineReorderEligibility' => true,
            'vendorReference' => '',
            'productTaxAmount' => 5
        ];

        $this->assertEquals(
            $expectedResult,
            $this->graphqlApiHelperMock->getLineItemRateDetails($rateResponse, $itemId)
        );
    }

    /**
     * Test method to test RateResponse With Exception errors
     *
     * @return void
     */
    public function testGetRateResponseWithExceptionErrors()
    {
        $errorResponse = ['errors' => 'Test error'];
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->itemMock->method('getOptionByCode')->willReturnSelf();
        $this->itemMock->method('getValue')
        ->willReturn('{"external_prod":[{"key":"value"}],
        "originalFiles":[{"key":"value"}],
        "discountIntent":"your_discount_value"}');
        $this->fxoRateQuote->expects($this->once())
             ->method('getFXORateQuote')
             ->willReturn($errorResponse);
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Rate API Error: API failure');

        $this->graphqlApiHelperMock->getRateResponse($this->quoteMock, null);
    }

    /**
     * Test method to test RateResponseWith Errors
     *
     * @return void
     */
    public function testGetRateResponseWithErrors()
    {
        $errorResponse = ['errors' => [['message' => 'Test error']]];
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->itemMock->method('getOptionByCode')->willReturnSelf();
        $this->itemMock->method('getValue')
        ->willReturn('{"external_prod":[{"key":"value"}],
        "originalFiles":[{"key":"value"}],
        "discountIntent":"your_discount_value"}');
        $this->fxoRateQuote->expects($this->once())
             ->method('getFXORateQuote')
             ->willReturn($errorResponse);
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Rate API Error: Test error');

        $this->graphqlApiHelperMock->getRateResponse($this->quoteMock, null);
    }

    /**
     * Test method to test RateResponse With Exception errors
     *
     * @return void
     */
    public function testGetRateResponseWithValidResonse()
    {
        $rateRespone =  [
            'output' => [
                'rateQuote' => [
                    "rateQuoteDetails" => [
                        [
                            "productLines" => [
                                [
                                    "instanceId" => "140538",
                                    "priceable" => true,
                                ],
                                [
                                    "instanceId" => "140538",
                                    "priceable" => false,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
         ];
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->itemMock->method('getOptionByCode')->willReturnSelf();
        $this->itemMock->method('getValue')
        ->willReturn('{"external_prod":[{"key":"value"}],
        "originalFiles":[{"key":"value"}],
        "discountIntent":"your_discount_value"}');
        $this->fxoRateQuote->expects($this->once())
             ->method('getFXORateQuote')
             ->willReturn($rateRespone);

        $this->graphqlApiHelperMock->getRateResponse($this->quoteMock, null);
    }

    /**
     * Test method to test AddLogsForGraphqlApi
     *
     * @return void
     */
    public function testAddLogsForGraphqlApi()
    {
        $logData = [
            'query' => 'test-query'
        ];
        $sessionId = 'test-session-id';
        $this->sessionMock->method('getSessionId')
            ->willReturn($sessionId);
        $expectedLog = 'Fedex\UploadToQuote\Helper\GraphqlApiHelper::addLogsForGraphqlApi:';
        $this->loggerMock->expects($this->atMost(3))
            ->method('info')
            ->with($this->stringContains($expectedLog));

        $this->graphqlApiHelperMock->addLogsForGraphqlApi($logData);
    }

     /**
      * Test method to test RateResponse With Exception errors
      *
      * @return void
      */
    public function testGetRetailCustomerIdWithException()
    {
        $this->cartIntegrationRepositoryMock
            ->expects($this->once())
            ->method('getByQuoteId')
            ->with($this->quoteMock->getId())
            ->willThrowException(new \Exception());

        $result = $this->graphqlApiHelperMock->getRetailCustomerId($this->quoteMock);
        $this->assertEquals('', $result);
    }

    /**
     * Test method to test quotebiddinginstoreupdatesFixToggle
     *
     * @return void
     */
    public function testQuotebiddinginstoreupdatesFixToggle()
    {
        $expectedValue = 1;
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Fedex\UploadToQuote\Helper\GraphqlApiHelper::FUSE_BIDDING_IN_STORE_UPDATES,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->graphqlApiHelperMock->quotebiddinginstoreupdatesFixToggle());
    }

    /**
     * Test method for getRateSummaryDataForApprovedQuote
     *
     * @return void
     */
    public function testGetRateSummaryDataForApprovedQuote()
    {
        $orderIncrementId = '100000001';

        $expectedOrderData = [
            'subtotal' => 200,
            'discount_amount' => 10,
            'subtotal_incl_tax' => 210,
            'tax_amount' => 20,
            'grand_total' => 220,
            'total_fees' => 5,
            'shipping_amount' => 15,
        ];

        $this->order->expects($this->any())
            ->method('create')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();

        $this->orderMock->expects($this->any())
            ->method('getData')
            ->willReturn($expectedOrderData);
        $result = $this->graphqlApiHelperMock->getRateSummaryDataForApprovedQuote($orderIncrementId);
        $this->assertEquals("Actual", $result['estimatedVsActual']);
    }

    /**
 * Test method for getQuoteLineItemsForApprovedQuote
 *
 * @return void
 */
public function testGetQuoteLineItemsForApprovedQuote()
{
    $orderIncrementId = '100000002';

    // Mock order item
    $orderItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'getProductOptions',
            'getName',
            'getId',
            'getQtyOrdered',
            'getPrice',
            'getDiscountAmount',
            'getRowTotal',
            'getMiraklOfferId'
        ])
        ->getMock();

    $productOptions = [
        'info_buyRequest' => [
            'external_prod' => [
                [
                    'userProductName' => 'Custom Product Name',
                    'foo' => 'bar'
                ]
            ],
            'originalFiles' => [
                [
                    'file' => 'file1.pdf'
                ]
            ]
        ]
    ];

    $orderItemMock->expects($this->any())
        ->method('getProductOptions')
        ->willReturn($productOptions);

    $orderItemMock->expects($this->any())
        ->method('getName')
        ->willReturn('Fallback Name');

    $orderItemMock->expects($this->any())
        ->method('getId')
        ->willReturn(42);

    $orderItemMock->expects($this->any())
        ->method('getQtyOrdered')
        ->willReturn(3);

    $orderItemMock->expects($this->any())
        ->method('getPrice')
        ->willReturn(99.99);

    $orderItemMock->expects($this->any())
        ->method('getDiscountAmount')
        ->willReturn(10.00);

    $orderItemMock->expects($this->any())
        ->method('getRowTotal')
        ->willReturn(289.97);

    $orderItemMock->expects($this->any())
        ->method('getMiraklOfferId')
        ->willReturn(null);

    // Mock order
    $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
        ->disableOriginalConstructor()
        ->setMethods(['loadByIncrementId', 'getAllVisibleItems'])
        ->getMock();

    $orderMock->expects($this->once())
        ->method('loadByIncrementId')
        ->with($orderIncrementId)
        ->willReturnSelf();

    $orderMock->expects($this->once())
        ->method('getAllVisibleItems')
        ->willReturn([$orderItemMock]);

    // Mock order factory
    $this->order->expects($this->once())
        ->method('create')
        ->willReturn($orderMock);

    // Mock getLineItemRateDetailsForApprovedQuote
    $expectedLineItemRateDetails = ['foo' => 'bar'];
    $graphqlApiHelper = $this->graphqlApiHelperMock;
    $reflection = new \ReflectionClass($graphqlApiHelper);
    $method = $reflection->getMethod('getLineItemRateDetailsForApprovedQuote');
    $method->setAccessible(true);

    // Use PHPUnit's getMockForAbstractClass to override the method
    $graphqlApiHelper = $this->getMockBuilder(GraphqlApiHelper::class)
        ->setConstructorArgs([
            $this->contextMock,
            $this->commentRepoMock,
            $this->commentFactoryMock,
            $this->customerRepositoryMock,
            $this->companyRepositoryMock,
            $this->cartDataHelperMock,
            $this->searchCriteriaBuilderMock,
            $this->timezoneInterface,
            $this->adminConfigHelperMock,
            $this->negotiableQuoteRepository,
            $this->quoteHistoryMock,
            $this->scopeConfigMock,
            $this->fxoRateQuote,
            $this->publishMock,
            $this->marsConfigMock,
            $this->sessionMock,
            $this->loggerMock,
            $this->quoteFactory,
            $this->cartIntegrationRepositoryMock,
            $this->fuseBidViewModelMock,
            $this->order,
            $this->toggleConfigMock
        ])
        ->setMethods(['getLineItemRateDetailsForApprovedQuote'])
        ->getMock();

    $graphqlApiHelper->expects($this->once())
        ->method('getLineItemRateDetailsForApprovedQuote')
        ->with($orderItemMock)
        ->willReturn($expectedLineItemRateDetails);

    $this->toggleConfigMock->expects($this->once())
        ->method('getToggleConfigValue')
        ->with(GraphqlApiHelper::TIGER_D236536)
        ->willReturn(false);

    $result = $graphqlApiHelper->getQuoteLineItemsForApprovedQuote($orderIncrementId);

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals(42, $result[0]['item_id']);
    $this->assertEquals('Custom Product Name', $result[0]['name']);
    $this->assertEquals(3, $result[0]['qty']);
    $this->assertEquals(99.99, $result[0]['price']);
    $this->assertEquals(10.00, $result[0]['discount_amount']);
    $this->assertEquals(99.99, $result[0]['base_price']);
    $this->assertEquals(289.97, $result[0]['row_total']);
    $this->assertEquals(json_encode($productOptions['info_buyRequest']['external_prod'][0]), $result[0]['product']);
    $this->assertEquals(json_encode($productOptions['info_buyRequest']['originalFiles'][0]), $result[0]['original_files']);
    $this->assertEquals($expectedLineItemRateDetails, $result[0]['lineItemRateDetails']);
    $this->assertTrue($result[0]['editable']);
}

/**
 * Test getQuoteLineItemsForApprovedQuote with MiraklOfferId set (editable = false)
 *
 * @return void
 */
public function testGetQuoteLineItemsForApprovedQuoteWithMiraklOfferId()
{
    $orderIncrementId = '100000003';

    $orderItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'getProductOptions',
            'getName',
            'getId',
            'getQtyOrdered',
            'getPrice',
            'getDiscountAmount',
            'getRowTotal',
            'getMiraklOfferId'
        ])
        ->getMock();

    $productOptions = [
        'info_buyRequest' => [
            'external_prod' => [
                [
                    'userProductName' => 'Another Product',
                ]
            ]
        ]
    ];

    $orderItemMock->expects($this->any())
        ->method('getProductOptions')
        ->willReturn($productOptions);

    $orderItemMock->expects($this->any())
        ->method('getName')
        ->willReturn('Fallback Name');

    $orderItemMock->expects($this->any())
        ->method('getId')
        ->willReturn(99);

    $orderItemMock->expects($this->any())
        ->method('getQtyOrdered')
        ->willReturn(1);

    $orderItemMock->expects($this->any())
        ->method('getPrice')
        ->willReturn(10.00);

    $orderItemMock->expects($this->any())
        ->method('getDiscountAmount')
        ->willReturn(2.00);

    $orderItemMock->expects($this->any())
        ->method('getRowTotal')
        ->willReturn(8.00);

    $orderItemMock->expects($this->any())
        ->method('getMiraklOfferId')
        ->willReturn('mirakl-offer-id');

    $this->order->expects($this->once())
        ->method('create')
        ->willReturn($this->orderMock);

    $this->orderMock->expects($this->once())
        ->method('loadByIncrementId')
        ->with($orderIncrementId)
        ->willReturnSelf();

    $this->orderMock->expects($this->once())
        ->method('getAllVisibleItems')
        ->willReturn([$orderItemMock]);

    // Mock getLineItemRateDetailsForApprovedQuote
    $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
        ->setConstructorArgs([
            $this->contextMock,
            $this->commentRepoMock,
            $this->commentFactoryMock,
            $this->customerRepositoryMock,
            $this->companyRepositoryMock,
            $this->cartDataHelperMock,
            $this->searchCriteriaBuilderMock,
            $this->timezoneInterface,
            $this->adminConfigHelperMock,
            $this->negotiableQuoteRepository,
            $this->quoteHistoryMock,
            $this->scopeConfigMock,
            $this->fxoRateQuote,
            $this->publishMock,
            $this->marsConfigMock,
            $this->sessionMock,
            $this->loggerMock,
            $this->quoteFactory,
            $this->cartIntegrationRepositoryMock,
            $this->fuseBidViewModelMock,
            $this->order,
            $this->toggleConfigMock
        ])
        ->setMethods(['getLineItemRateDetailsForApprovedQuote'])
        ->getMock();

    $this->graphqlApiHelperMock->expects($this->once())
        ->method('getLineItemRateDetailsForApprovedQuote')
        ->with($orderItemMock)
        ->willReturn(['bar' => 'baz']);

    $this->toggleConfigMock->expects($this->once())
        ->method('getToggleConfigValue')
        ->with(GraphqlApiHelper::TIGER_D236536)
        ->willReturn(false);

    $result = $this->graphqlApiHelperMock->getQuoteLineItemsForApprovedQuote($orderIncrementId);

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals(99, $result[0]['item_id']);
    $this->assertEquals('Another Product', $result[0]['name']);
    $this->assertEquals(1, $result[0]['qty']);
    $this->assertEquals(10.00, $result[0]['price']);
    $this->assertEquals(2.00, $result[0]['discount_amount']);
    $this->assertEquals(10.00, $result[0]['base_price']);
    $this->assertEquals(8.00, $result[0]['row_total']);
    $this->assertEquals(json_encode($productOptions['info_buyRequest']['external_prod'][0]), $result[0]['product']);
    $this->assertEquals('', $result[0]['original_files']);
    $this->assertEquals(['bar' => 'baz'], $result[0]['lineItemRateDetails']);
    $this->assertFalse($result[0]['editable']);
}

/**
 * Test getLineItemRateDetailsForApprovedQuote returns correct data when productRateTotal is present
 */
public function testGetLineItemRateDetailsForApprovedQuoteWithProductRateTotal()
{
    $itemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProductOptions'])
        ->getMock();

    $productRateTotal = [
        [
            'instanceId' => 1,
            'productId' => 101,
            'type' => 'foo',
            'name' => 'Bar',
            'userProductName' => 'Baz',
            'unitQuantity' => 2,
            'unitOfMeasurement' => 'EA',
            'priceable' => true,
            'productRetailPrice' => 100,
            'productLinePrice' => 90,
            'productDiscountAmount' => 10,
            'productLineDiscounts' => ['discount1'],
            'productLineDetails' => ['detail1'],
            'links' => ['link1'],
            'specialInstructions' => 'none',
            'reorderCatalogReference' => 'ref',
            'lineReorderEligibility' => true,
            'vendorReference' => 'vendor',
            'productTaxAmount' => 5
        ]
    ];

    $itemMock->expects($this->once())
        ->method('getProductOptions')
        ->willReturn(['info_buyRequest' => ['productRateTotal' => $productRateTotal]]);

    $result = $this->graphqlApiHelperMock->getLineItemRateDetailsForApprovedQuote($itemMock);

    $this->assertIsArray($result);
    $this->assertEquals(1, $result['instanceId']);
    $this->assertEquals(101, $result['productId']);
    $this->assertEquals('foo', $result['type']);
    $this->assertEquals('Bar', $result['name']);
    $this->assertEquals('Baz', $result['userProductName']);
    $this->assertEquals(2, $result['unitQuantity']);
    $this->assertEquals('EA', $result['unitOfMeasurement']);
    $this->assertTrue($result['priceable']);
    $this->assertEquals(100, $result['productRetailPrice']);
    $this->assertEquals(90, $result['productLinePrice']);
    $this->assertEquals(10, $result['productDiscountAmount']);
    $this->assertEquals(['discount1'], $result['productLineDiscounts']);
    $this->assertEquals(['detail1'], $result['productLineDetails']);
    $this->assertEquals(['link1'], $result['links']);
    $this->assertEquals('none', $result['specialInstructions']);
    $this->assertEquals('ref', $result['reorderCatalogReference']);
    $this->assertTrue($result['lineReorderEligibility']);
    $this->assertEquals('vendor', $result['vendorReference']);
    $this->assertEquals(5, $result['productTaxAmount']);
}

/**
 * Test getLineItemRateDetailsForApprovedQuote returns empty array when productRateTotal is missing
 */
public function testGetLineItemRateDetailsForApprovedQuoteWithNoProductRateTotal()
{
    $itemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProductOptions'])
        ->getMock();

    $itemMock->expects($this->once())
        ->method('getProductOptions')
        ->willReturn(['info_buyRequest' => []]);

    $result = $this->graphqlApiHelperMock->getLineItemRateDetailsForApprovedQuote($itemMock);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

/**
 * Test getLineItemRateDetailsForApprovedQuote returns all keys as null if productRateTotal has empty array
 */
public function testGetLineItemRateDetailsForApprovedQuoteWithEmptyProductRateTotal()
{
    $itemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProductOptions'])
        ->getMock();

    $itemMock->expects($this->once())
        ->method('getProductOptions')
        ->willReturn(['info_buyRequest' => ['productRateTotal' => [[]]]]);

    $result = $this->graphqlApiHelperMock->getLineItemRateDetailsForApprovedQuote($itemMock);

    $this->assertIsArray($result);
    // All keys should be present and null
    $expectedKeys = [
        'instanceId',
        'productId',
        'type',
        'name',
        'userProductName',
        'unitQuantity',
        'unitOfMeasurement',
        'priceable',
        'productRetailPrice',
        'productLinePrice',
        'productDiscountAmount',
        'productLineDiscounts',
        'productLineDetails',
        'links',
        'specialInstructions',
        'reorderCatalogReference',
        'lineReorderEligibility',
        'vendorReference',
        'productTaxAmount'
    ];
    foreach ($expectedKeys as $key) {
        $this->assertArrayHasKey($key, $result);
        $this->assertNull($result[$key]);
    }
}

}



