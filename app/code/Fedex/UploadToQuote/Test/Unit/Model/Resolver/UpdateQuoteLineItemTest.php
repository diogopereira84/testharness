<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Model\Resolver;

use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Fedex\UploadToQuote\Model\Resolver\UpdateQuoteLineItem;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\Model\Query\Context;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote\ItemFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use \Psr\Log\LoggerInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\AlertsMapper;

class UpdateQuoteLineItemTest extends TestCase
{
    protected $itemMock;
    protected $itemOption;
    protected $quoteMock;
    protected $negotiableQuote;
    /**
     * @var (\Fedex\UploadToQuote\Helper\QuoteEmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteEmailHelperMock;
    protected const QUOTE_ID = 5;
    protected const ITEM_ID = 1;
    protected const INFO_BUY_REQUEST = 'info_buyRequest';
    protected const PRODUCT = '{"id":1456773326927,"name":"Multi Sheet","qty":1,"priceable":true}';
    protected const USER_PRODUCT_NAME = "Poster Prints";
    protected const COMMENT = "Test Comment";
    protected const ARGS_VALID =  [
        'input' => [
            'uid' => '5',
            'quote_action' => 'save',
            'comment' => 'test',
            'quote_items' => [
                [
                    'item_id' => 1,
                    'item_action' => 'update',
                    'product' => self::PRODUCT,
                ],
                [
                    'item_id' => 2,
                    'item_action' => 'update',
                    'product' => self::PRODUCT,
                ],
            ],
        ],
    ];

    protected const ARGS_VALID_FOR_SENT =  [
        'input' => [
            'uid' => '5',
            'quote_action' => 'sent_to_customer',
            'comment' => 'test',
            'quote_items' => [
                [
                    'item_id' => 1,
                    'item_action' => 'update',
                    'product' => self::PRODUCT,
                ],
                [
                    'item_id' => 2,
                    'item_action' => 'update',
                    'product' => self::PRODUCT,
                ],
            ],
        ],
    ];
    protected const DECODED_DATA = [
        'external_prod' => [
            [
                'userProductName' => self::USER_PRODUCT_NAME,
                'id' => 1466693799380,
                'version' => 2,
                'name' => 'Posters',
                'qty' => 1,
                'priceable' => 1,
                'instanceId' => 1632939962051,
            ],
        ],
    ];


    public const FUSE_SAVE_TO_QUOTE_BEFORE_RATE_QUOTE =
        'environment_toggle_configuration/environment_toggle/mazegeeks_d209908';

    public const FUSE_SAVE_TO_QUOTE_NOTES_FIX =
        'environment_toggle_configuration/environment_toggle/mazegeek_u2q_quote_notes_save_fix';

    /** @var UpdateQuoteLineItem|MockObject*/
    private $updateQuoteLineItemMock;

    /** @var Context|MockObject*/
    protected $contextMock;

    /** @var Field|MockObject */
    private $fieldMock;

    /** @var ResolveInfo|MockObject */
    private $resolveInfoMock;

    /**  @var SerializerInterface|MockObject */
    private $serializer;

    /**  @var QuoteIdMask|MockObject */
    private $quoteIdMaskResource;

    /** @var CartRepositoryInterface|MockObject */
    private $quoteRepository;

    /**  @var GraphqlApiHelper|MockObject */
    private $graphqlApiHelperMock;

    /** @var ScopeConfigInterface $scopeConfigMock */
    protected $scopeConfigMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryMock;

    /**
     * @var ItemFactory $quoteItemFactoryMock
     */
    protected $quoteItemFactoryMock;

    /**
     * @var FuseBidViewModel|MockObject
     */
    private $fuseBidViewModelMock;

    /**
     * @var LoggerHelper|MockObject
     */
    protected $loggerHelper;

    /**
     * @var NewRelicHeaders|MockObject
     */
    protected $newRelicHeaders;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelper;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var AlertsMapper|MockObject
     */
    private $alertsMapperMock;

    /**
     * Init mocks for tests.
     *
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
            ->onlyMethods(['get', 'save'])
            ->getMockForAbstractClass();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['unserialize', 'serialize'])
            ->getMockForAbstractClass();

        $this->quoteIdMaskResource = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnmaskedQuoteId'])
            ->getMock();

        $this->itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->onlyMethods(['getOptionByCode', 'getItemId', 'setQty', 'save', 'addOption', 'setProduct'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemOption = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue', 'getItemId', 'setValue', 'save', 'addOption'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
            ->setMethods(['getQuoteInfo', 'getQuoteLineItems', 'getFxoAccountNumberOfQuote',
                'getQuoteCompanyName', 'setQuoteNotes', 'getQuoteNotes','changeQuoteStatus',
                'getFuseAddToQuoteProduct','getRateResponse','addLogsForGraphqlApi',
                'getRetailCustomerId','getQuoteContactInfo','quotesavebeforeratequoteFixToggle',
                'quotebiddinginstoreupdatesFixToggle', 'isTigerRetailUploadToQuoteEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'save',
                    'getId',
                    'getAllItems',
                    'removeItem',
                    'getExtensionAttributes',
                    'getNegotiableQuote',
                    'addItem',
                    'getActive',
                    'setActive',
                    'setCouponCode',
                    'getSnapshot',
                    'collectTotals'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->setMethods(['setStatus', 'save', 'getEmail', 'getStatus', 'getSnapshot','setSnapshot'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteEmailHelperMock = $this->getMockBuilder(QuoteEmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'sendQuoteGenericEmail',
            ])->getMockForAbstractClass();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getById',
            ])
            ->getMockForAbstractClass();

        $this->quoteItemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
            ])->getMock();

        $this->fuseBidViewModelMock = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerHelper = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->newRelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->alertsMapperMock = $this->getMockBuilder(AlertsMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateQuoteLineItemMock = new updateQuoteLineItem(
            $this->quoteRepository,
            $this->serializer,
            $this->quoteIdMaskResource,
            $this->graphqlApiHelperMock,
            $this->quoteEmailHelperMock,
            $this->productRepositoryMock,
            $this->quoteItemFactoryMock,
            $this->fuseBidViewModelMock,
            $this->loggerHelper,
            $this->newRelicHeaders,
            $this->logger,
            $this->adminConfigHelper,
            $this->toggleConfig,
            $this->alertsMapperMock
        );
    }

    /**
     * Call Common Function.
     *
     * @return void
     */
    public function callCommonFunction()
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
        $this->resolveInfoMock->fieldName = 'testField';
        $this->fuseBidViewModelMock->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->with(static::QUOTE_ID)
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('getActive')
            ->willReturn(1);
        $this->quoteMock->expects($this->any())
            ->method('setActive')
            ->willReturnSelf();
        $this->quoteIdMaskResource->expects($this->any())
            ->method('getUnmaskedQuoteId')
            ->willReturn(5);
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock,$this->itemMock]);
        $this->graphqlApiHelperMock->expects($this->any())
            ->method("quotebiddinginstoreupdatesFixToggle")
            ->willReturn(true);
        $this->quoteMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->itemMock->expects($this->any())
            ->method('getItemId')
            ->will($this->onConsecutiveCalls(1, 2));
        $this->itemMock->expects($this->any())
            ->method('getOptionByCode')
            ->with(self::INFO_BUY_REQUEST)
            ->willReturn($this->itemOption);
        $this->itemOption->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode(self :: DECODED_DATA));
        $this->itemOption->expects($this->any())
            ->method('setValue')
            ->willReturn($this->itemOption);
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(self :: DECODED_DATA);
        $this->itemOption->expects($this->any())
            ->method('getItemId')
            ->will($this->onConsecutiveCalls(1, 2));
        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('AnyString');
        $this->adminConfigHelper->expects($this->any())
            ->method('isToggleB2564807Enabled')
            ->willReturn(true);
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getQuoteInfo')
            ->willReturn($quoteInfo);
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('isTigerRetailUploadToQuoteEnabled')
            ->willReturn(true);
        $this->quoteMock->expects($this->any())
            ->method('collectTotals')
            ->willReturnSelf();
    }

    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     */
    public function testResolveValidScenario()
    {
        $mutationName = 'updateNegotiableQuote';
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
        $fxoRate = [
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
        $this->callCommonFunction();

        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($fxoRate);
        $this->itemMock->expects($this->any())
            ->method('setQty')
            ->with(1)
            ->willReturn($this->itemMock);
        $this->itemMock->expects($this->any())
            ->method('save')
            ->willReturn($this->itemMock);

        $this->graphqlApiHelperMock->expects($this->once())
            ->method('quotesavebeforeratequoteFixToggle')
            ->willReturn($fxoRate);

        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            self :: ARGS_VALID
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }


    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     */
    public function testResolveForQuoteActionSentToCustomer()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'comment' => self :: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];

        $this->callCommonFunction();
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveForQuoteActionNbcSupport()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'nbc_support',
                'comment' => self :: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];

        $this->callCommonFunction();
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     */
    public function testResolveForQuoteActionNbcPriced()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'nbc_priced',
                'comment' => self :: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];

        $this->callCommonFunction();
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveForQuoteActioRevisionRequested()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'revision_requested',
                'comment' => self :: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];

        $this->callCommonFunction();
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method only for comment
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveForOnlyComment()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self :: COMMENT
            ],
        ];

        $this->callCommonFunction();
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method with a valid scenario.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveForCloseAction()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'close',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $fxoRate = [
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
        $this->callCommonFunction();
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($fxoRate);
        $result = $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );

        $this->assertArrayHasKey('line_items', $result);
        $this->assertArrayHasKey('activities', $result);
    }

    /**
     * Test the resolve method with exception.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionError()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 2,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 3,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];
        $this->callCommonFunction();
        $missingItem = '3';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            'Provided item_id 3 does not belong to the given quote',
            $missingItem
        );
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for close action.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForDelete()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];
        $this->resolveInfoMock->fieldName = 'testField';
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->with(static::QUOTE_ID)
            ->willReturn($this->quoteMock);
        $this->quoteIdMaskResource->expects($this->any())
            ->method('getUnmaskedQuoteId')
            ->willReturn(5);
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->any())
            ->method('getItemId')
            ->willReturn(1);
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Cannot delete the only item in the quote');

        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for close action.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForDeleteSentToCustomer()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('For sent_to_customer, item action cannot be add or delete');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for close action.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForDeleteCatchBlock()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id'  => 1,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id'  => 2,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $fxoRate = [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD'
                ]
            ]
        ];
        $this->callCommonFunction();
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($fxoRate);
        $phrase = new Phrase(__('Error while deleting quote_item'));
        $exception = new GraphQlInputException($phrase);
        $this->quoteMock->expects($this->any())
            ->method('removeItem')
            ->willThrowException($exception);
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Error while deleting quote_item');

        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method testExceptionEmptyQuoteItemsInSentToCustomer
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionEmptyQuoteItemsInSentToCustomer()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'comment' => self:: COMMENT
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('quote_items cannot be empty');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method testExceptionEmptyQuoteItemIdInSentToCustomer
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionEmptyQuoteItemIdInSentToCustomer()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self::COMMENT,
                'quote_items' => [
                    [
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('item_id and product are essential to update the item.');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception for priceable items.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForPricableItems()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => '{"id":1456773326927,"name":"Multi Sheet","qty":1,"priceable":false}',
                    ]
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('For sent_to_customer, only priceable items should be there');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with invalid quote action
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForInvalidQuoteAction()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save123',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $this->resolveInfoMock->fieldName = 'testField';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Invalid Quote action. Allowed values are: save, sent_to_customer, close');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with invalid item action
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testExceptionForInvalidItemAction()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'delete123',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Invalid item action. Allowed values are: update, delete');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method for validateStatusChangeAction
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateStatusChangeAction()
    {
        $this->negotiableQuote->method('getStatus')
            ->willReturn('ordered');
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'comment' => 'test',
                'nbc_required' => false,
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 2,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You cannot change quote status from ordered to SENT');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }


    /**
     * Test method for validateAndSaveComment
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateAndSaveComment()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'close',
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Both comment and quote_items cannot be empty');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method for validateAndSaveComment
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateForEmptyQuoteItems()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('quote_items cannot be empty');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method for testValidateForSentToCustomerAdd
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateForSentToCustomerAdd()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
                'quote_items' => [
                    [
                        'item_id' => 1,
                        'item_action' => 'add',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('For sent_to_customer, item action cannot be add or delete');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method for testValidateForSentToCustomerEmptyQuoteItems
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateForSentToCustomerEmptyQuoteItems()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'sent_to_customer',
            ]
        ];
        $this->callCommonFunction();
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('quote_items cannot be empty');
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test method for Add Quote Item
     *
     * @return void
     */
    public function testAddNewQuoteItem()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self::COMMENT,
                'quote_items' => [
                    [
                        'item_action' => 'add',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $fxoRate = [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD'
                ]
            ]
        ];
        $this->callCommonFunction();
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($fxoRate);
        $product = $this->createMock(Product::class);
        $productId = 2;
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getFuseAddToQuoteProduct')
            ->willReturn($productId);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);
        $this->quoteItemFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->itemMock);
        $this->itemMock->expects($this->any())
            ->method('addOption')
            ->willReturnSelf();
        $this->itemMock->expects($this->any())
            ->method('setProduct')
            ->willReturnSelf();
        $this->itemMock->expects($this->any())
            ->method('setQty')
            ->willReturnSelf();
        $this->itemMock->expects($this->any())
            ->method('setQty')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('addItem')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
        $this->assertTrue(true);
    }

    /**
     * Test method for Add Quote Item
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testDeleteQuoteItem()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'save',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id'  => 1,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id'  => 2,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ]
                ],
            ],
        ];
        $fxoRate = [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD'
                ]
            ]
        ];
        $this->callCommonFunction();
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getRateResponse')
            ->willReturn($fxoRate);
        $this->quoteMock->expects($this->any())
            ->method('removeItem')
            ->willReturnSelf();

        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
        $this->assertTrue(true);
    }


    /**
     * Test method to ValidateForRevisionRequested.
     *
     * @throws GraphQlInputException
     * @return void
     */
    public function testValidateForRevisionRequested()
    {
        $args = [
            'input' => [
                'uid' => '5',
                'quote_action' => 'revision_requested',
                'comment' => self:: COMMENT,
                'quote_items' => [
                    [
                        'item_id' => 2,
                        'item_action' => 'update',
                        'product' => self::PRODUCT,
                    ],
                    [
                        'item_id' => 3,
                        'item_action' => 'delete',
                        'product' => self::PRODUCT,
                    ],
                ],
            ],
        ];
        $this->callCommonFunction();
        $missingItem = '3';
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage(
            'Provided item_id 3 does not belong to the given quote',
            $missingItem
        );
        $this->updateQuoteLineItemMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * @return void
     */
    /**
     * @return void
     */
    public function testSaveCouponCode()
    {
        $couponCode = 'TESTCODE';
        $headerArray = ['header' => 'value'];
        $this->negotiableQuote->expects($this->exactly(2))
            ->method('getSnapshot')
            ->willReturn(serialize(['quote' => ['some' => 'data']]));
        $this->negotiableQuote->expects($this->once())
            ->method('setSnapshot')
            ->with($this->anything());
        $this->negotiableQuote->expects($this->once())
            ->method('save');

        $extensionAttributesMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributesMock->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);

        $this->quoteMock->expects($this->once())
            ->method('setCouponCode')
            ->with($couponCode);
        $this->quoteMock->expects($this->once())
            ->method('save')
            ->with($this->quoteMock);
        $this->quoteMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->willReturn(['quote' => ['some' => 'data']]);
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('serialized_snapshot');

        $this->loggerHelper->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Coupon code set: ' . $couponCode)
            );

        $this->updateQuoteLineItemMock->saveCouponCode($this->quoteMock, $couponCode);
    }
}
