<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Model\Resolver;

use Fedex\Logger\Model\Log;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Fedex\UploadToQuote\Model\Resolver\GetAllQuotes;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GraphQl\Model\Query\Context;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Filter;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\UploadToQuote\Api\NegotiableQuoteIntegrationInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class GetAllQuotesTest extends TestCase
{
    protected $searchCriteriaMock;
    /**
     * @var (\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteInterface;
    protected $negotiableQuote;
    protected $quoteList;
    protected $quote;
    protected $resultMock;
    protected $itemsMock;
    protected $sortOrderMock;
    private const QUOTE_CREATION_DATE = '2021-11-13';
    private const QUOTE_PRICE = '10';
    private const END_DATE = '2021-12-15';
    private const CUSTOMER_EMAIL = 'test@gmail.com';
    private const CUSTOMER_FNAME = 'test';
    private const CUSTOMER_LNAME = 'test';
    private const CUSTOMER_PHONE = '12345';

    /** @var GetAllQuotesResolver|MockObject*/
    private $getAllQuotesResolverMock;

    /** @var Context|MockObject*/
    protected $contextMock;

    /** @var NegotiableQuoteRepositoryInterface|MockObject */
    private $negotiableQuoteRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;

    /** @var Field|MockObject */
    private $fieldMock;

    /** @var ResolveInfo|MockObject */
    private $resolveInfoMock;

    /** @var QuoteIdMask|MockObject*/
    private $quoteIdMaskResource;

    /** @var TimezoneInterface $timezoneMock */
    protected $timezoneMock;

    /** @var SortOrderBuilder|MockObject */
    private $sortOrderBuilderMock;

    /** @var  GraphqlApiHelper|MockObject */
    private $graphqlApiHelperMock;

    /** @var FuseBidViewModel|MockObject */
    private $fuseBidViewModelMock;

    /** @var FilterBuilder|MockObject */
    protected $filterBuilderMock;

    /**
     * @var NewRelicHeaders
     */
    protected $newrelicHeaders;

    /**
     * @var LoggerHelper
     */
    protected $loggerHelper;

    /**
     * @var ConfigInterface
     */
    protected $instoreConfig;

    /**
     * @var NegotiableQuoteIntegrationInterface
     */
    protected $negotiableQuoteIntegration;

    /**
     * @var AdminConfigHelper
     */
    protected $adminConfigHelper;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create','addFilters'])
            ->getMock();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['setField', 'setConditionType','setValue','create'])
            ->getMock();

        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);

        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getById'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->setMethods(['getStatus', 'getData', 'getExpirationPeriod'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteList = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getData',
                    'getCustomerFirstname',
                    'getCustomerLastname',
                    'getCustomerEmail',
                    'getCustomerTelephone',
                    'getCreatedAt',
                    'getUpdatedAt',
                    'getId',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultMock = $this->createMock(SearchResultsInterface::class);

        $this->itemsMock = [
            $this->createMock(Quote::class),
            $this->createMock(Quote::class),
        ];

        $this->quoteIdMaskResource = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMaskedQuoteId'])
            ->getMock();

        $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
            ->setMethods(['getQuoteContactInfo', 'getQuoteNotes','getQuoteInfo','addLogsForGraphqlApi'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['setField', 'setDirection', 'create'])
            ->getMock();

        $this->sortOrderMock = $this->getMockBuilder(SortOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fuseBidViewModelMock = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();

        $this->newrelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerHelper = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instoreConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteIntegration =  $this->createMock(NegotiableQuoteIntegrationInterface::class);

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getAllQuotesResolverMock = new GetAllQuotes(
            $this->negotiableQuoteRepository,
            $this->searchCriteriaBuilder,
            $this->quoteIdMaskResource,
            $this->timezoneMock,
            $this->graphqlApiHelperMock,
            $this->sortOrderBuilderMock,
            $this->fuseBidViewModelMock,
            $this->filterBuilderMock,
            $this->instoreConfig,
            $this->loggerHelper,
            $this->newrelicHeaders,
            $this->negotiableQuoteIntegration,
            $this->adminConfigHelper
        );
    }

    /**
     * Common code to call in multiple functions.
     *
     * @return void
     */
    public function commonCodeToCall()
    {
        $quoteInfo = [
            'quote_id' => "123",
            'quote_status' => "created",
            'quote_creation_date' => "20-10-2023",
            'quote_updated_date' => "21-10-2023",
            'quote_submitted_date' => "21-10-2023",
            'quote_expiration_date' => "22-10-2023",
            'gross_amount' => "223",
            'discount_amount' => "23",
            'quote_total' => "200",
            'hub_centre_id' => "test",
            'location_id' => "test"
        ];
        $quoteNames = ['Upload To Quote Creation', 'FUSE bidding Quote Creation'];
        $filterMock = $this->createMock(Filter::class);
        $this->quote
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->resolveInfoMock->fieldName = 'testField';
        $this->quoteList->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->quote]);
        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->quoteList);
        $this->filterBuilderMock->expects($this->any())
            ->method('setField')
            ->with('quote_name')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setConditionType')
            ->with('in')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setValue')
            ->with($quoteNames)
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($filterMock);
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilters')
            ->with([$filterMock])
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->resultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->itemsMock]);
        $this->resultMock->expects($this->any())
            ->method('getTotalCount')
            ->willReturn(count($this->itemsMock));
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getQuoteInfo')
            ->willReturn($quoteInfo);
    }

    /**
     * Common code to call in multiple quotes functions.
     *
     * @return void
     */
    public function callCodeForMultipleQuotesFunction()
    {
        $this->commonCodeToCall();
        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->resultMock);
        $this->fuseBidViewModelMock->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getById')
            ->willReturn($this->createMock(NegotiableQuote::class));
        $this->quote
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn(self::QUOTE_CREATION_DATE);
        $this->quote
            ->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn(self::QUOTE_CREATION_DATE);
        $this->quote
            ->expects($this->any())
            ->method('getCustomerFirstname')
            ->willReturn(self::CUSTOMER_FNAME);
        $this->quote
            ->expects($this->any())
            ->method('getCustomerLastname')
            ->willReturn(self::CUSTOMER_LNAME);
        $this->quote
            ->expects($this->any())
            ->method('getCustomerTelephone')
            ->willReturn(self::CUSTOMER_PHONE);
        $this->quote
            ->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn(self::CUSTOMER_EMAIL);
        $this->negotiableQuote->expects($this->any())
            ->method('getData')
            ->willReturn(['quote_mgnt_location_code' => '1234']);
        $this->negotiableQuote->expects($this->any())
            ->method('getData')
            ->willReturn(['negotiated_total_price' => '343']);
    }
    /**
     * Test the resolve method with a valid scenario for quote_id.
     *
     * @return void
     */
    public function testResolveValidScenarioForQuoteId()
    {
        $args = [
            'filter' => [
                'quote_id' => '123',
            ],
        ];
        $this->commonCodeToCall();
        $result = $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('quotes', $result);
    }

    /**
     * Test the resolve method with a valid scenario without quote_id.
     *
     * @return void
     */
    public function testResolveValidScenarioWithoutQuoteId()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                    'last_name' => self::CUSTOMER_LNAME,
                    'email' => self::CUSTOMER_EMAIL,
                    'phone_number' => self::CUSTOMER_PHONE,
                ],
                'date_filter' => [
                    'type' => 'CREATED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::END_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
                'order_by' => 'quote_id',
                'order' => 'DESC',
                'nbc_required'=>1
            ],
        ];
        $this->callCodeForMultipleQuotesFunction();
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
            ->willReturn("2023-02-01 01:01:01");
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('setDirection')
            ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->sortOrderMock);

        $result = $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('quotes', $result);
    }

    /**
     * Test the resolve method with a valid scenario for contact info.
     *
     * @return void
     */
    public function testResolveValidScenarioForContactInfo()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                    'last_name' => self::CUSTOMER_LNAME,
                    'email' => self::CUSTOMER_EMAIL,
                    'phone_number' => '123456',
                ],
                'date_filter' => [
                    'type' => 'EXPIRED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
            ],
        ];
        $this->callCodeForMultipleQuotesFunction();
        $result = $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('quotes', $result);
    }

    /**
     * Test the resolve method with exception for date filter .
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForDateFilter()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                ],
            ],
        ];
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "date_filter is mandatory"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for price filter .
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForPriceFilterEmptyValues()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                ],
                'date_filter' => [
                    'type' => 'EXPIRED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => ''
                ],
            ],
        ];
        $this->fuseBidViewModelMock->expects($this->any())
        ->method('isFuseBidToggleEnabled')
        ->willReturn(true);
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "price_filter min_price is required"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for price filter .
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForPriceFilterNonNumericString()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                ],
                'date_filter' => [
                    'type' => 'EXPIRED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => 'abc'
                ],
            ],
        ];
        $this->fuseBidViewModelMock->expects($this->any())
        ->method('isFuseBidToggleEnabled')
        ->willReturn(true);
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Please enter a number"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for price filter .
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForPriceFilterOutOfRange()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                ],
                'date_filter' => [
                    'type' => 'EXPIRED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => '-1'
                ],
            ],
        ];
        $this->fuseBidViewModelMock->expects($this->any())
        ->method('isFuseBidToggleEnabled')
        ->willReturn(true);
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Allowed price filter min_price values are: 0 - 999999"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Common code for Exception functions
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function commonCodeForExceptionsFunctions()
    {
        $quoteNames = ['Upload To Quote Creation'];
        $filterMock = $this->createMock(Filter::class);
        $this->filterBuilderMock->expects($this->any())
            ->method('setField')
            ->with('quote_name')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setConditionType')
            ->with('in')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setValue')
            ->with($quoteNames)
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($filterMock);
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilters')
            ->with([$filterMock])
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
    }
    /**
     * Test the resolve method with exception for contact info filter .
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForContactInfoFilter()
    {
        $args = [
            'filter' => [
                'quote_status' => 'CREATED',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                    'last_name' => self::CUSTOMER_LNAME,
                    'email' => self::CUSTOMER_EMAIL,
                    'phone_number' => '1',
                ],
                'date_filter' => [
                    'type' => 'EXPIRED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
            ],
        ];
        $this->commonCodeForExceptionsFunctions();
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Minimun 2 characters needed to serach for First Name, Last Name, Email & Phone."
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for invalid status.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForInvaliStatus()
    {
        $args = [
            'filter' => [
                'quote_status' => 'created',
                'hub_centre_id' => '123',
                'contact_info' => [
                    'first_name' => self::CUSTOMER_FNAME,
                ],
                'date_filter' => [
                    'type' => 'CREATED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
            ],
        ];
        $this->commonCodeForExceptionsFunctions();
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Invalid status. Allowed values are: CREATED, EXPIRED, SENT, CANCELED, REQUEST"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for invalid date type.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForInvaliDateType()
    {
        $args = [
            'filter' => [
                'date_filter' => [
                    'type' => 'test',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
            ],
        ];
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Invalid type. Allowed values are: CREATED, EXPIRED"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for invalid order by.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForInvalidOrderBy()
    {
        $args = [
            'filter' => [
                'date_filter' => [
                    'type' => 'CREATED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::QUOTE_CREATION_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
                'order_by' => 'test',
                'order' => 'DESC',
            ],
        ];
        $this->commonCodeForExceptionsFunctions();
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "Invalid order_by value. Allowed values are: quote_id, hub_centre_id, quote_status, email, first_name, last_name, phone_number, quote_creation_date, quote_updated_date, quote_expiration_date"
        );
        $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with a valid scenario without quote_id.
     *
     * @return void
     */
    public function testResolveValidScenarioWithoutQuoteStatus()
    {
        $args = [
            'filter' => [
                'date_filter' => [
                    'type' => 'CREATED',
                    'start_date' => self::QUOTE_CREATION_DATE,
                    'end_date' => self::END_DATE,
                ],
                'price_filter' => [
                    'min_price' => self::QUOTE_PRICE
                ],
                'order_by' => 'quote_status',
                'order' => 'DESC',
            ],
        ];
        $this->callCodeForMultipleQuotesFunction();
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
        ->willReturn("2023-02-01 01:01:01");
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('setDirection')
            ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->sortOrderMock);

        $result = $this->getAllQuotesResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('quotes', $result);
    }

    /**
     * Test the resolve method with quote_status as order_by and ASC order
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testHandleSortingForQuoteStatusASC()
    {
        $quotes = [
            ['quote_status' => 'Pending'],
            ['quote_status' => 'Approved'],
            ['quote_status' => 'Rejected']
        ];
        $order = 'ASC';
        $sortedQuotes = $this->getAllQuotesResolverMock->handleSortingForQuoteStatus($quotes, $order);

        $expectedQuotes = [
            ['quote_status' => 'Approved'],
            ['quote_status' => 'Pending'],
            ['quote_status' => 'Rejected']
        ];

        $this->assertEquals($expectedQuotes, $sortedQuotes);
    }

    /**
     * Test the resolve method with quote_status as order_by and DESC order
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testHandleSortingForQuoteStatusDESC()
    {
        $quotes = [
            ['quote_status' => 'Pending'],
            ['quote_status' => 'Approved'],
            ['quote_status' => 'Rejected']
        ];
        $order = 'DESC';
        $sortedQuotes = $this->getAllQuotesResolverMock->handleSortingForQuoteStatus($quotes, $order);

        $expectedQuotes = [
            ['quote_status' => 'Rejected'],
            ['quote_status' => 'Pending'],
            ['quote_status' => 'Approved']
        ];

        $this->assertEquals($expectedQuotes, $sortedQuotes);
    }

}
