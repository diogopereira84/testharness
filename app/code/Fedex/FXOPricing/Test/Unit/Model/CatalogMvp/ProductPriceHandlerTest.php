<?php

declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\CatalogMvp;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\CatalogMvp\ProductPriceHandler;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Price\TierPricePersistence;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetSearchResultsInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductPriceHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Api\SearchCriteriaInterface
     * Mock object for SearchCriteria used in unit tests.
     */
    protected $searchCriteriaMock;
    /**
     * @var ProductPriceHandler Instance of the ProductPriceHandler class used for testing product price handling logic.
     */
    private ProductPriceHandler $productPriceHandler;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the Store Manager used in unit tests.
     */
    private $storeManagerMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Customer\Model\Session
     * Mock object for the customer session used in unit tests.
     */
    private $customerSessionMock;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the Product Repository used for testing purposes.
     */
    private $productRepositoryMock;
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the AttributeSetRepositoryInterface used in unit testing.
     */
    private $attributeSetRepositoryMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for tier price persistence used in unit testing.
     */
    private $tierPricePersistenceMock;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for SearchCriteriaBuilder used in unit tests.
     */
    private $searchCriteriaBuilderMock;
    /**
     * @var \Magento\Framework\Api\FilterBuilder|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the FilterBuilder class used in unit testing.
     */
    private $filterBuilderMock;
    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the FilterBuilder class used in unit testing.
     */
    private $quoteMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Store\Model\Store
     * Mock object for the Store model used in unit tests.
     */
    private $storeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\Customer
     * Mock object for the Customer model used in unit tests.
     */
    private $customerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\CustomerMagento\Eav\Api\Data\AttributeSetSearchResultsInterface
     */
    private $searchResultMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Magento\Framework\Api\Filter
     */
    private $filterMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
     */
    private $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Fedex\EnvironmentManager\ViewModel\ToggleConfig
     */
    private $toggleConfigMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Quote\Model\Quote\Item
     */
    private $itemMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Magento\Eav\Api\Data\AttributeSetInterface
     */
    private $attributeSetMock;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the ProductInterface used in unit tests.
     */
    private $productInterfaceMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['get', 'getId', 'getAttributeSetId', 'getData', 'getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeSetRepositoryMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
        ->setMethods(['getList'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->tierPricePersistenceMock = $this->createMock(TierPricePersistence::class);
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilters', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->setMethods(['setField', 'setValue', 'setConditionType', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this->createMock(StoreInterface::class);
        $this->customerMock = $this->createMock(Customer::class);
        $this->searchResultMock = $this->getMockBuilder(AttributeSetSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filterMock = $this->createMock(Filter::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue', 'isToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeSetMock = $this->getMockBuilder(AttributeSetInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getId', 'getRowId', 'getAttributeSetId', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->productPriceHandler = new ProductPriceHandler(
            $this->storeManagerMock,
            $this->customerSessionMock,
            $this->productRepositoryMock,
            $this->attributeSetRepositoryMock,
            $this->tierPricePersistenceMock,
            $this->searchCriteriaBuilderMock,
            $this->filterBuilderMock,
            $this->loggerMock,
            $this->toggleConfigMock
        );
    }

    public function testHandle(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
    
        $this->filterBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($this->searchResultMock);
        $this->searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->attributeSetMock]);
        $this->attributeSetMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(1);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->productInterfaceMock->expects($this->any())
            ->method('getRowId')
            ->willReturn(123);

        $this->productInterfaceMock->expects($this->any())
            ->method('getData')
            ->willReturn(['tier_price' => ['123' => ['price' => 100]]]);

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLines' => [
                                        [
                                            'productLinePrice' =>  100.00,
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }

    public function testHandleWhenToggleIsOff(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
    
        $this->filterBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($this->searchResultMock);
        $this->searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->attributeSetMock]);
        $this->attributeSetMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(1);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLines' => [
                                        [
                                            'productLinePrice' =>  100.00,
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }

    public function testHandleWhenAttributeisNull(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);
    
        $this->filterBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($this->searchResultMock);
        $this->searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->attributeSetMock]);
        $this->attributeSetMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(null);

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLines' => [
                                        [
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }

    public function testHandleWhenPriceIsNotSet(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(0);
    
        $this->filterBuilderMock->expects($this->once())
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($this->searchResultMock);
        $this->searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->attributeSetMock]);
        $this->attributeSetMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(1);

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLines' => [
                                        [
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }

    public function testHandleWhenProductLinesIsNotSet(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->productRepositoryMock);

        $this->productRepositoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(0);
    
        $this->filterBuilderMock->expects($this->any())
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->any())
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->any())
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->attributeSetRepositoryMock->expects($this->any())
            ->method('getList')
            ->willReturn($this->searchResultMock);
        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->attributeSetMock]);
        $this->attributeSetMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(1);

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLine' => [
                                        [
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }
    
    public function testHandleException(): void
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('getGroupId')
            ->willReturn(1);
        $exception = \Exception::class;
        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willThrowException(new $exception("Error occurred"));

        $this->loggerMock->expects($this->once())
            ->method('critical');

        $rateApiOutputData = [
                    'output' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'productLines' => [
                                        [
                                            'unitQuantity' => 3
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
            ];
        $this->assertEquals("", $this->productPriceHandler->handle($this->quoteMock, $rateApiOutputData));
    }
}
