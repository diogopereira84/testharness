<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Model;

use Fedex\Mars\Model\QuoteProcess;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationItemRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\NegotiableQuote\Api\CommentLocatorInterface;
use Magento\NegotiableQuote\Model\HistoryRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\ResourceModel\Comment;
use Magento\Framework\Api\SearchResults;
use Magento\NegotiableQuote\Model\ResourceModel\History;
use Magento\Quote\Model\ResourceModel\Quote\Payment\Collection as PaymentCollection;
use Magento\Quote\Model\ResourceModel\Quote\Payment;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as ItemCollection;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item\Option;
use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection as AddressCollection;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection as ShippingRateCollection;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\ResourceModel\Quote\Integration;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use ReflectionClass;

class QuoteProcessTest extends TestCase
{
    /**
     * @var (\Fedex\Cart\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteRepositoryMock;

    /**
     * @var (\Fedex\Cart\Api\CartIntegrationRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $integrationRepositoryMock;
    /**
     * @var (\Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $integrationNoteMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Framework\Api\SearchCriteriaBuilder)
     */
    protected $searchCriteriaBuilderMock;

    /**
     * @var (\Fedex\Cart\Api\CartIntegrationItemRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $integrationItemModelMock;

    /**
     * @var (\Magento\NegotiableQuote\Api\CommentLocatorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteCommentModelMock;

    /**
     * @var (\Magento\NegotiableQuote\Model\HistoryRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteHistoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\Quote)
     */
    protected $quoteInterfaceMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Api\Data\CartExtensionInterface)
     */
    protected $extensionAttributesMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface)
     */
    protected $negotiableQuoteModelMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\NegotiableQuote\Model\ResourceModel\Comment)
     */
    protected $negotiableQuoteCommentMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Framework\Api\SearchResults)
     */
    protected $negotiableQuoteHistorySearchMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Framework\Api\SearchCriteriaInterface)
     */
    protected $searchCriteriaMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Framework\Api\SearchResultsInterface)
     */
    protected $searchResultsMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\NegotiableQuote\Model\ResourceModel\History)
     */
    protected $negotiableQuoteHistoryModelMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Payment\Collection)
     */
    protected $quotePaymentCollectionMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Payment)
     */
    protected $quotePaymentItemMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Item\Collection)
     */
    protected $quoteItemCollectionMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Item)
     */
    protected $quoteItemMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Item\Option)
     */
    protected $quoteItemOptionMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Address)
     */
    protected $quoteItemAddressMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Cart\Api\Data\CartIntegrationItemInterface)
     */
    protected $integrationItemMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Api\Data\CartItemExtensionInterface)
     */
    protected $itemExtensionAttributesMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface)
     */
    protected $negotiableQuoteItemMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\Address\Collection)
     */
    protected $quoteAddressCollectionMock;

    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection)
     */
    protected $shippingRateCollectionMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\ResourceModel\Quote\ShippingRate)
     */
    protected $shippingRateItemnMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Cart\Model\Quote\IntegrationNote)
     */
    protected $integrationNoteItemMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Cart\Model\ResourceModel\Quote\Integration)
     */
    protected $integrationMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Fedex\Mars\Model\QuoteProcess
     */
    protected $quoteProcessMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Mars\Model\Config)
     */
    protected $configMock;

    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();

        $this->integrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteId'])
            ->getMockForAbstractClass();

        $this->integrationNoteMock = $this->getMockBuilder(CartIntegrationNoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getList'])
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFilter', 'create'])
            ->getMock();
        
        $this->integrationItemModelMock = $this->getMockBuilder(CartIntegrationItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteItemId'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteCommentModelMock = $this->getMockBuilder(CommentLocatorInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getListForQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteHistoryMock = $this->getMockBuilder(HistoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getList'])
            ->getMockForAbstractClass();
            
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['critical'])
            ->getMockForAbstractClass();

        $this->quoteInterfaceMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getExtensionAttributes', 'getPaymentsCollection',
                'getItemsCollection', 'getAddressesCollection'])
            ->getMock();

        $this->extensionAttributesMock = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteModelMock = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteId'])
            ->addMethods(['getData'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteCommentMock = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->negotiableQuoteHistorySearchMock = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResultsMock = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteHistoryModelMock = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->quotePaymentCollectionMock = $this->getMockBuilder(PaymentCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();
        
        $this->quotePaymentItemMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->quoteItemCollectionMock = $this->getMockBuilder(ItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();
        
        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getOptions', 'getAddress', 'getExtensionAttributes', 'getItemId'])
            ->getMock();

        $this->quoteItemOptionMock = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->quoteItemAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getShippingRatesCollection'])
            ->getMock();

        $this->integrationItemMock = $this->getMockBuilder(CartIntegrationItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();

        $this->itemExtensionAttributesMock = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNegotiableQuoteItem'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteItemMock = $this->getMockBuilder(NegotiableQuoteItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();

        $this->quoteAddressCollectionMock = $this->getMockBuilder(AddressCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->shippingRateCollectionMock = $this->getMockBuilder(ShippingRateCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->shippingRateItemnMock = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->integrationNoteItemMock = $this->getMockBuilder(IntegrationNote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->integrationMock = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMock();

        $this->configMock = $this->getMockBuilder(\Fedex\Mars\Model\Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMazeGeeksB2743693Enabled','isQuoteIdentifierEnabled'])
            ->getMock();

        $this->configMock->method('isMazeGeeksB2743693Enabled')->willReturn(true);

        $this->objectManager = new ObjectManager($this);
        $this->quoteProcessMock = $this->objectManager->getObject(
            QuoteProcess::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'integrationRepository' => $this->integrationRepositoryMock,
                'integrationNote' => $this->integrationNoteMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'integrationItem' => $this->integrationItemModelMock,
                'negotiableQuoteComment' => $this->negotiableQuoteCommentModelMock,
                'negotiableQuoteHistory' => $this->negotiableQuoteHistoryMock,
                'logger' => $this->loggerMock,
                'config' => $this->configMock,
            ]
        );
    }

    /**
     * Test getQuoteJson
     *
     * @param int $id
     * @param array $expectedOrderData
     *
     * @dataProvider getSimpleQuoteTableDataDataProvider
     * @return void
     */
    public function testGetQuoteJson($id, $expectedQuoteData)
    {
        $this->quoteRepositoryMock->method('get')->willReturn($this->quoteInterfaceMock);
        $this->quoteInterfaceMock->method('getExtensionAttributes')->willReturn($this->extensionAttributesMock);
        $this->extensionAttributesMock->method('getNegotiableQuote')->willReturn($this->negotiableQuoteModelMock);
        $this->negotiableQuoteModelMock->method('getData')->willReturn(['quote_id' => '2']);
        $this->quoteInterfaceMock->method('getPaymentsCollection')->willReturn($this->quotePaymentCollectionMock);
        $this->quoteInterfaceMock->method('getAddressesCollection')->willReturn($this->quoteAddressCollectionMock);
        
        $this->testGetQuoteData();
        $this->testGetNegotiableQuoteData();
        $this->testGetSimpleQuoteTableDataPayment();
        $this->testGetQuoteItemData();
        $this->testGetQuoteAddressData();
        $this->testGetQuoteIntegrationNoteData();
        $this->testGetQuoteIntegrationData();
    }

    /**
     * Test getQuoteData
     *
     * @return void
     */
    public function testGetQuoteData()
    {
        $this->quoteInterfaceMock->method('getData')->willReturn(['entity_id' => '1']);

        $this->assertIsArray($this->quoteProcessMock->getQuoteData($this->quoteInterfaceMock));
    }

    /**
     * Test getNegotiableQuoteData
     *
     * @return void
     */
    public function testGetNegotiableQuoteData()
    {
        $this->negotiableQuoteCommentModelMock->method('getListForQuote')
            ->willReturn([$this->negotiableQuoteCommentMock]);
        $this->negotiableQuoteCommentMock->method('getData')->willReturn(['entity_id' => '3']);
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->negotiableQuoteHistoryMock->method('getList')
            ->with($this->equalTo($this->searchCriteriaMock))
            ->willReturn($this->negotiableQuoteHistorySearchMock);
        $this->testGetSimpleQuoteTableDataNegotiableQuoteHistory();

        $this->assertIsArray(
            $this->quoteProcessMock->getNegotiableQuoteData(
                $this->negotiableQuoteModelMock,
                ['quote_id' => '3'],
                true
            )
        );
    }

    /**
     * Test getSimpleQuoteTableData for Negotiable Quote History
     *
     * @return void
     */
    public function testGetSimpleQuoteTableDataNegotiableQuoteHistory()
    {
        $this->negotiableQuoteHistorySearchMock->method('getItems')
            ->willReturn([$this->negotiableQuoteHistoryModelMock]);
        $this->negotiableQuoteHistoryModelMock->method('getData')->willReturn(['history_id' => '1']);
 
        $this->assertIsArray($this->quoteProcessMock->getSimpleQuoteTableData($this->negotiableQuoteHistorySearchMock));
    }

    /**
     * Test getSimpleQuoteTableData for Quote Payment
     *
     * @return void
     */
    public function testGetSimpleQuoteTableDataPayment()
    {
        $this->quotePaymentCollectionMock->method('getItems')->willReturn([$this->quotePaymentItemMock]);
        $this->quotePaymentItemMock->method('getData')->willReturn(['payment_id' => '1']);
 
        $this->assertIsArray($this->quoteProcessMock->getSimpleQuoteTableData($this->quotePaymentCollectionMock));
    }

    /**
     * Test getQuoteItemData
     *
     * @return void
     */
    public function testGetQuoteItemData()
    {
        $this->quoteItemCollectionMock->method('getItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getData')->willReturn(['item_id' => '1']);
        $this->quoteItemMock->method('getOptions')->willReturn([$this->quoteItemOptionMock]);
        $this->quoteItemMock->method('getItemId')->willReturn(1);
        $this->quoteItemOptionMock->method('getData')->willReturn(['option_id' => '1']);
        $this->quoteItemMock->method('getAddress')->willReturn($this->quoteItemAddressMock);
        $this->quoteItemAddressMock->method('getData')->willReturn(['address_id' => '1']);
        $this->integrationItemModelMock->method('getByQuoteItemId')->willReturn($this->integrationItemMock);
        $this->integrationItemMock->method('getData')->willReturn(['integration_item_id' => '1']);
        $this->quoteItemMock->method('getExtensionAttributes')->willReturn($this->itemExtensionAttributesMock);
        $this->itemExtensionAttributesMock->method('getNegotiableQuoteItem')
            ->willReturn($this->negotiableQuoteItemMock);
        $this->negotiableQuoteItemMock->method('getData')->willReturn(['quote_item_id' => '1']);

        $this->assertIsArray(
            $this->quoteProcessMock->getQuoteItemData(
                $this->quoteItemCollectionMock,
                true
            )
        );
    }

    /**
     * Test getQuoteAddressData
     *
     * @return void
     */
    public function testGetQuoteAddressData()
    {
        $this->quoteAddressCollectionMock->method('getItems')->willReturn([$this->quoteItemAddressMock]);
        $this->quoteItemAddressMock->method('getData')->willReturn(['address_id' => '2']);
        $this->quoteItemAddressMock->method('getShippingRatesCollection')
            ->willReturn($this->shippingRateCollectionMock);
        $this->testGetSimpleQuoteTableDataShippingRates();
        
        $this->assertIsArray(
            $this->quoteProcessMock->getQuoteAddressData(
                $this->quoteAddressCollectionMock
            )
        );
    }

    /**
     * Test getSimpleQuoteTableData for Quote Address Shipping Rate
     *
     * @return void
     */
    public function testGetSimpleQuoteTableDataShippingRates()
    {
        $this->shippingRateCollectionMock->method('getItems')->willReturn([$this->shippingRateItemnMock]);
        $this->shippingRateItemnMock->method('getData')->willReturn(['rate_id' => '1']);
 
        $this->assertIsArray($this->quoteProcessMock->getSimpleQuoteTableData($this->shippingRateCollectionMock));
    }

    /**
     * Test test getQuoteIntegrationNoteData
     *
     * @return void
     */
    public function testGetQuoteIntegrationNoteData()
    {

        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchResultsMock);
        $this->searchResultsMock->method('getItems')->willReturn([$this->integrationNoteItemMock]);
        $this->integrationNoteItemMock->method('getData')->willReturn(['entity_id' => '2']);
    }

    /**
     * Test test getQuoteIntegrationData
     *
     * @return void
     */
    public function testGetQuoteIntegrationData()
    {
        $this->integrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);
        $this->integrationMock->method('getData')->willReturn(['integration_id' => '1']);

        $this->assertIsArray(
            $this->quoteProcessMock->getQuoteIntegrationData(1)
        );
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getSimpleQuoteTableDataDataProvider(): array
    {
        $expectedQuoteResult = [
            [
                'entity_id' => '1',
                'negotiable_quote' => [
                    [
                        'quote_id' => '3',
                        'negotiable_quote_comment' => [
                            [
                                'entity_id' => '3'
                            ]
                        ],
                        'negotiable_quote_history' => [
                            [
                                'history_id' => '1'
                            ]
                        ]
                    ]
                ],
                'quote_payment' => [
                    [
                        'payment_id' => '1'
                    ]
                ],
                'quote_item' => [
                    [
                        'item_id' => '1',
                        'quote_item_option' => [
                            [
                                'option_id' => '1'
                            ]
                        ],
                        'quote_address_item' => [
                            [
                                'address_id' => '1'
                            ]
                        ],
                        'quote_integration_item' => [
                            [
                                'integration_item_id' => '1'
                            ]
                        ],
                        'negotiable_quote_item' => [
                            [
                                'quote_item_id' => '1'
                            ]
                        ]
                    ]
                ],
                'quote_address' => [
                    [
                        'address_id' => '2',
                        'quote_shipping_rate' => [
                            [
                                'rate_id' => '1'
                            ]
                        ]
                    ]
                ],
                'quote_integration_note' => [
                    [
                        'entity_id' => '2'
                    ]
                ],
                'quote_integration' => [
                    [
                        'integration_id' => '1'
                    ]
                ]
            ]
        ];

        return [
            [1, $expectedQuoteResult]
        ];
    }

    /**
     * Test getSimpleQuoteTableData with encoding
     *
     * @return void
     */
    public function testGetSimpleQuoteTableDataWithEncoding()
    {
        $arrayData = ['test' => 'value', 'nested' => ['key' => 'value']];
        
        // Mock collection item
        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        
        $itemMock->method('getData')->willReturn([
            'id' => 1,
            'encoded_field' => $arrayData,
            'normal_field' => 'text'
        ]);

        // Mock collection
        $collectionMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();
        
        $collectionMock->method('getItems')->willReturn([$itemMock]);

        $result = $this->quoteProcessMock->getSimpleQuoteTableData(
            $collectionMock,
            'Fedex\Mars\Model\QuoteProcess::checkIsNull',
            0,
            true,
            'encoded_field'
        );
        $this->assertEquals('text', $result[0]['normal_field']);
    }

    /**
     * Setup mocks for complete flow
     *
     * @return void
     */
    private function setupMocksForCompleteFlow()
    {
        // Setup payment collection
        $this->quotePaymentCollectionMock->method('getItems')->willReturn([$this->quotePaymentItemMock]);
        $this->quotePaymentItemMock->method('getData')->willReturn(['payment_id' => '1']);

        // Setup item collection
        $this->quoteItemCollectionMock->method('getItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getData')->willReturn(['item_id' => '1']);
        $this->quoteItemMock->method('getOptions')->willReturn([$this->quoteItemOptionMock]);
        $this->quoteItemMock->method('getItemId')->willReturn(1);
        $this->quoteItemOptionMock->method('getData')->willReturn(['option_id' => '1']);
        $this->quoteItemMock->method('getAddress')->willReturn($this->quoteItemAddressMock);
        $this->quoteItemAddressMock->method('getData')->willReturn(['address_id' => '1']);
        $this->integrationItemModelMock->method('getByQuoteItemId')->willReturn($this->integrationItemMock);
        $this->integrationItemMock->method('getData')->willReturn(['integration_item_id' => '1']);
        $this->quoteItemMock->method('getExtensionAttributes')->willReturn($this->itemExtensionAttributesMock);
        $this->itemExtensionAttributesMock->method('getNegotiableQuoteItem')
            ->willReturn($this->negotiableQuoteItemMock);
        $this->negotiableQuoteItemMock->method('getData')->willReturn(['quote_item_id' => '1']);

        // Setup address collection
        $this->quoteAddressCollectionMock->method('getItems')->willReturn([$this->quoteItemAddressMock]);
        $this->quoteItemAddressMock->method('getShippingRatesCollection')
            ->willReturn($this->shippingRateCollectionMock);
        $this->shippingRateCollectionMock->method('getItems')->willReturn([$this->shippingRateItemnMock]);
        $this->shippingRateItemnMock->method('getData')->willReturn(['rate_id' => '1']);

        // Setup negotiable quote comment
        $this->negotiableQuoteCommentModelMock->method('getListForQuote')
            ->willReturn([$this->negotiableQuoteCommentMock]);
        $this->negotiableQuoteCommentMock->method('getData')->willReturn(['entity_id' => '3']);

        // Setup search criteria and history
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->negotiableQuoteHistoryMock->method('getList')->willReturn($this->negotiableQuoteHistorySearchMock);
        $this->negotiableQuoteHistorySearchMock->method('getItems')
            ->willReturn([$this->negotiableQuoteHistoryModelMock]);
        $this->negotiableQuoteHistoryModelMock->method('getData')->willReturn(['history_id' => '1']);

        // Setup integration note
        $this->integrationNoteMock->method('getList')->willReturn($this->searchResultsMock);
        $this->searchResultsMock->method('getItems')->willReturn([$this->integrationNoteItemMock]);
        $this->integrationNoteItemMock->method('getData')->willReturn(['entity_id' => '2']);

        // Setup integration
        $this->integrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);
        $this->integrationMock->method('getData')->willReturn(['integration_id' => '1']);
    }

    /**
     * Test getQuoteJson with quote identifier enabled
     *
     * @return void
     */
    public function testGetQuoteJsonWithQuoteIdentifierEnabled()
    {
        $id = 1;
        
        // Mock config to return true for quote identifier
        $configMock = $this->getMockBuilder(\Fedex\Mars\Model\Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMazeGeeksB2743693Enabled', 'isQuoteIdentifierEnabled'])
            ->getMock();
        
        $configMock->method('isMazeGeeksB2743693Enabled')->willReturn(true);
        $configMock->method('isQuoteIdentifierEnabled')->willReturn(true);

        $this->quoteRepositoryMock->method('get')->willReturn($this->quoteInterfaceMock);
        $this->quoteInterfaceMock->method('getData')->willReturn(['entity_id' => '1']);
        $this->quoteInterfaceMock->method('getExtensionAttributes')->willReturn($this->extensionAttributesMock);
        $this->extensionAttributesMock->method('getNegotiableQuote')->willReturn($this->negotiableQuoteModelMock);
        $this->negotiableQuoteModelMock->method('getData')->willReturn(['quote_id' => '2']);
        $this->negotiableQuoteModelMock->method('getQuoteId')->willReturn(2);
        $this->quoteInterfaceMock->method('getPaymentsCollection')->willReturn($this->quotePaymentCollectionMock);
        $this->quoteInterfaceMock->method('getItemsCollection')->willReturn($this->quoteItemCollectionMock);
        $this->quoteInterfaceMock->method('getAddressesCollection')->willReturn($this->quoteAddressCollectionMock);

        // Setup remaining mocks
        $this->setupMocksForCompleteFlow();

        $result = $this->quoteProcessMock->getQuoteJson($id);
        $this->assertEquals('1', $result[0]['entity_id']);
    }

    /**
     * Test getQuoteJson with NoSuchEntityException
     *
     * @return void
     */
    public function testGetQuoteJsonWithNoSuchEntityException()
    {
        $id = 999;
        $exceptionMessage = 'Quote not found';
        
        $this->quoteRepositoryMock->method('get')
            ->will($this->throwException(new \Magento\Framework\Exception\NoSuchEntityException(
                __($exceptionMessage)
            )));

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->with($this->stringContains('Unable to find quote id to build MARS upload to quote json'));

        $result = $this->quoteProcessMock->getQuoteJson($id);
        $this->assertSame([], $result);
    }

    /**
     * Test getQuoteJson with generic Exception
     *
     * @return void
     */
    public function testGetQuoteJsonWithGenericException()
    {
        $id = 1;
        $exceptionMessage = 'Something went wrong';
        
        $this->quoteRepositoryMock->method('get')
            ->will($this->throwException(new \Exception($exceptionMessage)));

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->with($this->stringContains('An error occurred while building MARS upload to quote json'));

        $result = $this->quoteProcessMock->getQuoteJson($id);
        
        $this->assertSame([], $result);
    }

    /**
     * Test getNegotiableQuoteData with MazeGeeks disabled
     *
     * @return void
     */
    public function testGetNegotiableQuoteDataWithMazeGeeksDisabled()
    {
        // Mock config to return false for MazeGeeks
        $configMock = $this->getMockBuilder(\Fedex\Mars\Model\Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMazeGeeksB2743693Enabled', 'isQuoteIdentifierEnabled'])
            ->getMock();
        
        $configMock->method('isMazeGeeksB2743693Enabled')->willReturn(false);
        $configMock->method('isQuoteIdentifierEnabled')->willReturn(false);

        $commentData = [
            'entity_id' => '3',
            'comment' => 'Test comment',
            'attachments' => ['file1.pdf', 'file2.doc'],
            'created_at' => '2024-01-01',
            'author_id' => '1'
        ];

        $this->negotiableQuoteCommentMock->method('getData')->willReturn($commentData);
        $this->negotiableQuoteCommentModelMock->method('getListForQuote')
            ->willReturn([$this->negotiableQuoteCommentMock]);
        
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->negotiableQuoteHistoryMock->method('getList')
            ->with($this->equalTo($this->searchCriteriaMock))
            ->willReturn($this->negotiableQuoteHistorySearchMock);
        $this->negotiableQuoteHistorySearchMock->method('getItems')
            ->willReturn([$this->negotiableQuoteHistoryModelMock]);
        $this->negotiableQuoteHistoryModelMock->method('getData')->willReturn(['history_id' => '1']);

        $result = $this->quoteProcessMock->getNegotiableQuoteData(
            $this->negotiableQuoteModelMock,
            ['quote_id' => '3'],
            true
        );
        $commentResult = $result['negotiable_quote_comment'][0];
        $this->assertEquals($commentData, $commentResult);
    }

    /**
     * Test getQuoteIntegrationData with NoSuchEntityException
     *
     * @return void
     */
    public function testGetQuoteIntegrationDataWithNoSuchEntityException()
    {
        $quoteId = 1;
        $exceptionMessage = 'Integration not found';
        
        $this->integrationRepositoryMock->method('getByQuoteId')
            ->will($this->throwException(new \Magento\Framework\Exception\NoSuchEntityException(
                __($exceptionMessage)
            )));

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->with($exceptionMessage);

        $result = $this->quoteProcessMock->getQuoteIntegrationData($quoteId);
        
        $this->assertSame([], $result);
    }

    /**
     * Test getQuoteItemData with NoSuchEntityException for integration item
     *
     * @return void
     */
    public function testGetQuoteItemDataWithNoSuchEntityException()
    {
        $exceptionMessage = 'Integration item not found';
        
        $this->quoteItemCollectionMock->method('getItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getData')->willReturn(['item_id' => '1']);
        $this->quoteItemMock->method('getOptions')->willReturn([$this->quoteItemOptionMock]);
        $this->quoteItemMock->method('getItemId')->willReturn(1);
        $this->quoteItemOptionMock->method('getData')->willReturn(['option_id' => '1']);
        $this->quoteItemMock->method('getAddress')->willReturn($this->quoteItemAddressMock);
        $this->quoteItemAddressMock->method('getData')->willReturn(['address_id' => '1']);
        
        // Mock the exception for integration item
        $this->integrationItemModelMock->method('getByQuoteItemId')
            ->will($this->throwException(
                new \Magento\Framework\Exception\NoSuchEntityException(
                    __($exceptionMessage)
                )
            ));
        
        $this->quoteItemMock->method('getExtensionAttributes')->willReturn($this->itemExtensionAttributesMock);
        $this->itemExtensionAttributesMock->method('getNegotiableQuoteItem')
            ->willReturn($this->negotiableQuoteItemMock);
        $this->negotiableQuoteItemMock->method('getData')->willReturn(['quote_item_id' => '1']);

        // Expect logger to be called with exception message
        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->with($exceptionMessage);

        $result = $this->quoteProcessMock->getQuoteItemData(
            $this->quoteItemCollectionMock,
            true
        );
        $this->assertEmpty($result[0]['quote_integration_item']);
    }

    /**
     * Test getQuoteItemData with MazeGeeks disabled (filters applied)
     *
     * @return void
     */
    public function testGetQuoteItemDataWithMazeGeeksDisabled()
    {
        // Mock config to return false for MazeGeeks
        $configMock = $this->getMockBuilder(\Fedex\Mars\Model\Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMazeGeeksB2743693Enabled', 'isQuoteIdentifierEnabled'])
            ->getMock();
        
        $configMock->method('isMazeGeeksB2743693Enabled')->willReturn(false);

        //Create QuoteProcess instance with MazeGeeks disabled
        $quoteProcessWithFilters = $this->objectManager->getObject(
            QuoteProcess::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'integrationRepository' => $this->integrationRepositoryMock,
                'integrationNote' => $this->integrationNoteMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'integrationItem' => $this->integrationItemModelMock,
                'negotiableQuoteComment' => $this->negotiableQuoteCommentModelMock,
                'negotiableQuoteHistory' => $this->negotiableQuoteHistoryMock,
                'logger' => $this->loggerMock,
                'config' => $configMock,
            ]
        );

        // Mock quote item data with fields that should be filtered out
        $quoteItemData = [
            'item_id' => '1',
            'product' => 'product_data', // should be filtered out
            'extension_attributes' => 'ext_attr_data', // should be filtered out
            'qty_options' => 'qty_options_data', // should be filtered out
            'tax_class_id' => 'tax_class_data', // should be filtered out
            'has_error' => true, // should be filtered out
            'sku' => 'TEST-SKU', // should remain
            'qty' => 2 // should remain
        ];

        $this->quoteItemCollectionMock->method('getItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getData')->willReturn($quoteItemData);
        $this->quoteItemMock->method('getOptions')->willReturn([$this->quoteItemOptionMock]);
        $this->quoteItemMock->method('getItemId')->willReturn(1);
        $this->quoteItemOptionMock->method('getData')->willReturn(['option_id' => '1']);
        $this->quoteItemMock->method('getAddress')->willReturn($this->quoteItemAddressMock);
        $this->quoteItemAddressMock->method('getData')->willReturn(['address_id' => '1']);
        $this->integrationItemModelMock->method('getByQuoteItemId')->willReturn($this->integrationItemMock);
        $this->integrationItemMock->method('getData')->willReturn(['integration_item_id' => '1']);
        $this->quoteItemMock->method('getExtensionAttributes')->willReturn($this->itemExtensionAttributesMock);
        $this->itemExtensionAttributesMock->method('getNegotiableQuoteItem')
            ->willReturn($this->negotiableQuoteItemMock);
        $this->negotiableQuoteItemMock->method('getData')->willReturn(['quote_item_id' => '1']);

        $result = $quoteProcessWithFilters->getQuoteItemData(
            $this->quoteItemCollectionMock,
            true
        );

        $this->assertEquals('TEST-SKU', $result[0]['sku']);
    }
}
