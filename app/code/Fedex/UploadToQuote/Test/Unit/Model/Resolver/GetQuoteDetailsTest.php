<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Model\Resolver;

use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Fedex\UploadToQuote\Model\Resolver\GetQuoteDetails;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Sales\Model\OrderFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class GetQuoteDetailsTest extends TestCase
{
    protected $fieldMock;
    protected $resolveInfoMock;
    protected $contextMock;
    protected $quoteMock;
    protected $getQuoteDetails;
    /**
     * @var QuoteIdMask $quoteIdMock
     */
    protected $quoteIdMock;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Option $optionMock
     */
    protected $optionMock;

    /**  @var GraphqlApiHelper|MockObject */
    private $graphqlApiHelperMock;

    /**
     * @var FuseBidViewModel|MockObject
     */
    private $fuseBidViewModelMock;

    protected $loggerHelper;
    protected $newRelicHeaders;

    /**
     * @var NegotiableQuoteFactory $negotiableQuoteFactory
     */
    protected $negotiableQuoteFactory;

    /**
     * @var NegotiableQuote $negotiableQuote
     */
    protected $negotiableQuote;

    /**
     * @var OrderFactory $orderFactoryMock
     */
    protected $orderFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $order;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->quoteIdMock = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnmaskedQuoteId'])
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
            ->setMethods(['getQuoteContactInfo', 'getQuoteLineItems', 'getFxoAccountNumberOfQuote',
                'getQuoteCompanyName', 'setQuoteNotes', 'getQuoteNotes','getQuoteInfo','getRateResponse'
                ,'addLogsForGraphqlApi', 'getRateSummaryDataForApprovedQuote', 'getQuoteLineItemsForApprovedQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fuseBidViewModelMock = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();

        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getStatus'])
            ->getMockForAbstractClass();
        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByIncrementId', 'getAllVisibleItems', 'getData'])
            ->getMockForAbstractClass();
        $this->loggerHelper = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->newRelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getQuoteDetails = new GetQuoteDetails(
            $this->quoteIdMock,
            $this->quoteRepository,
            $this->graphqlApiHelperMock,
            $this->fuseBidViewModelMock,
            $this->negotiableQuoteFactory,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    /**
     * Test function for valid arguments
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testResolveValidScenario()
    {
        $mutationName = 'getQuoteDetails';
        $headerArray = [];
        $this->fieldMock->expects($this->once())
            ->method('getName')
            ->willReturn($mutationName);

        $this->newRelicHeaders->expects($this->once())
            ->method('getHeadersForMutation')
            ->with($mutationName)
            ->willReturn($headerArray);

        $this->loggerHelper->expects($this->any())
            ->method('error')
            ->with(
                $this->stringContains('Magento graphQL start'),
                $headerArray
            );
        $args = [
            'uid' => '5MusfjiP3UKAAcpqodujoNpC9cJLdp6x',
            'contact_info' => [
                'email',
                'phone_number',
                'first_name',
                'last_name',
            ],
            'line_items' => [
                'item_id',
                'name',
                'qty',
                'price',
                'discount_amount',
                'base_price',
                'row_total',
                'product',
            ],
            'fxo_print_account_number',
            'company',
        ];
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
         $this->resolveInfoMock->fieldName = 'testField';
         $this->fuseBidViewModelMock->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
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

         $this->quoteIdMock
            ->expects($this->any())
            ->method('getUnmaskedQuoteId')
            ->willReturn(324798);

         $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->quoteMock);

         $this->graphqlApiHelperMock->expects($this->once())
            ->method('getQuoteInfo')
            ->willReturn($quoteInfo);
         $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($rateRespone);
        $this->negotiableQuoteFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('load')
            ->with(324798)
            ->willReturnSelf();
        $this->negotiableQuote->expects($this->any())
            ->method('getStatus')
            ->willReturn("created");
        $result = $this->getQuoteDetails->resolve(
             $this->fieldMock,
             $this->contextMock,
             $this->resolveInfoMock,
             null,
             $args
         );
        $this->assertEquals($quoteInfo['quote_id'], $result['quote_id']);
    }

    /**
     * Test function for valid arguments
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testResolveValidScenario2()
    {
        $args = [
            'uid' => '5MusfjiP3UKAAcpqodujoNpC9cJLdp6x',
            'contact_info' => [
                'email',
                'phone_number',
                'first_name',
                'last_name',
            ],
            'line_items' => [
                'item_id',
                'name',
                'qty',
                'price',
                'discount_amount',
                'base_price',
                'row_total',
                'product',
            ],
            'fxo_print_account_number',
            'company',
        ];
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
         $this->resolveInfoMock->fieldName = 'testField';
         $this->fuseBidViewModelMock->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
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

         $this->quoteIdMock
            ->expects($this->any())
            ->method('getUnmaskedQuoteId')
            ->willReturn(324798);

         $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->quoteMock);

         $this->graphqlApiHelperMock->expects($this->once())
            ->method('getQuoteInfo')
            ->willReturn($quoteInfo);
         $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($rateRespone);
        $this->negotiableQuoteFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('load')
            ->with(324798)
            ->willReturnSelf();
        $this->negotiableQuote->expects($this->any())
            ->method('getStatus')
            ->willReturn("ordered");
        $this->quoteMock->expects($this->once())
            ->method('getReservedOrderId')
            ->willReturn(324798);
        $this->graphqlApiHelperMock->expects($this->once())
            ->method('getRateSummaryDataForApprovedQuote')
            ->willReturn([
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    [
                        'productLines' => [
                            [
                                'instanceId' => '140538',
                                'priceable' => true,
                            ],
                            [
                                'instanceId' => '140538',
                                'priceable' => false,
                            ]
                        ]
                    ]
                ]
            ]);
        $this->graphqlApiHelperMock->expects($this->once())
            ->method('getQuoteLineItemsForApprovedQuote')
            ->willReturn([
                [
                    'item_id' => '123',
                    'name' => 'Test Item',
                    'qty' => 1,
                    'price' => 100.00,
                    'discount_amount' => 10.00,
                    'base_price' => 90.00,
                    'row_total' => 90.00,
                    'product' => [
                        'sku' => 'test-sku',
                        'name' => 'Test Product',
                        'type_id' => 'simple',
                        'url_key' => 'test-product',
                    ]
                ]
            ]);
        $this->orderFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->order);
        $this->order->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->order->expects($this->any())
            ->method('getData')
            ->willReturnSelf();

         $result = $this->getQuoteDetails->resolve(
             $this->fieldMock,
             $this->contextMock,
             $this->resolveInfoMock,
             null,
             $args
         );

        $this->assertArrayHasKey('contact_info', $result);
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testExceptionErrorForUid()
    {
        $args = [
            'uid' => '',
            'contact_info' => [
                'email',
                'phone_number',
                'first_name',
                'last_name',
            ],
            'line_items' => [
                'item_id',
                'name',
                'qty',
                'price',
                'discount_amount',
                'base_price',
                'row_total',
                'product',
            ],
            'fxo_print_account_number',
            'company',
        ];
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            "uid value must be specified."
        );
        $this->getQuoteDetails->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveThrowException()
    {
        $errorMsg = 'Some error message';
        $args = [
            'uid' => '5MusfjiP3UKAAcpqodujoNpC9cJLdp6x',
            'contact_info' => [
                'email',
                'phone_number',
                'first_name',
                'last_name',
            ],
            'line_items' => [
                'item_id',
                'name',
                'qty',
                'price',
                'discount_amount',
                'base_price',
                'row_total',
                'product',
            ],
            'fxo_print_account_number',
            'company',
        ];
        $this->quoteIdMock
            ->expects($this->once())
            ->method('getUnmaskedQuoteId')
            ->willThrowException(new \Exception($errorMsg));

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($errorMsg);
        $this->getQuoteDetails->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }
}
