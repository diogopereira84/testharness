<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Block\Product;

use Fedex\ProductCustomAtrribute\Model\Config\Backend as CanvaBackendConfig;
use Fedex\ProductEngine\Block\Product\Attributes;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View as BaseView;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url\EncoderInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private const ATTRIBUTE_CODE_HAS_CANVA_DESIGN = 'has_canva_design';
    private const MAX_ATTRIBUTES_AVAILABLE = 6;
    private const QUANTITY_ATTRIBUTE = 'quantity';
    private const TEXTBOX_ATTRIBUTE_OPTION = 'Textbox';

    protected Attributes $attributesMock;
    protected Context|MockObject $contextMock;
    protected Registry|MockObject $registryMock;
    protected StockRegistryInterface|MockObject $stockRegistryMock;
    protected StockItemInterface|MockObject $stockItemMock;
    protected EncoderInterface|MockObject $urlEncoderMock;
    protected JsonEncoderInterface|MockObject $jsonEncoderMock;
    protected StringUtils|MockObject $stringMock;
    protected Product|MockObject $productHelperMock;
    protected ConfigInterface|MockObject $productTypeConfigMock;
    protected FormatInterface|MockObject $localeFormatMock;
    protected Session|MockObject $customerSessionMock;
    protected ProductRepositoryInterface|MockObject $productRepositoryMock;
    protected ProductModel|MockObject $productMock;
    protected AttributeInterface|MockObject $attributeInterfaceMock;
    protected Store|MockObject $storeMock;
    protected PriceCurrencyInterface|MockObject $priceCurrencyMock;
    protected Attribute|MockObject $attributeMock;
    protected ProductAttributeRepositoryInterface|MockObject $attributeRepositoryMock;
    protected ProductAttributeInterface|MockObject $attributeAttributeMock;
    protected Table|MockObject $sourceMock;
    protected PeBackendConfig|MockObject $peBackendConfigMock;
    protected CanvaBackendConfig|MockObject $canvaBakendConfigMock;
    protected SearchCriteriaBuilder|MockObject $searchCriteriaBuilderMock;
    protected SearchCriteria|MockObject $searchCriteriaMock;
    protected ProductAttributeSearchResultsInterface|MockObject $productAttributeSearchResultsInterfaceMock;
    protected SortOrderBuilder|MockObject $sortOrderBuilderMock;
    protected SortOrder|MockObject $sortOrderMock;

    protected function setUp(): void
    {
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->onlyMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockRegistryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->onlyMethods(['getStockItem'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getRegistry', 'getStockRegistry'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->once())->method('getRegistry')->willReturn($this->registryMock);
        $this->contextMock->expects($this->once())->method('getStockRegistry')->willReturn($this->stockRegistryMock);
        $this->urlEncoderMock = $this->createMock(EncoderInterface::class);
        $this->jsonEncoderMock = $this->createMock(JsonEncoderInterface::class);
        $this->stringMock = $this->createMock(StringUtils::class);
        $this->productHelperMock = $this->createMock(Product::class);
        $this->productTypeConfigMock = $this->createMock(ConfigInterface::class);
        $this->localeFormatMock = $this->createMock(FormatInterface::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->productMock = $this->createMock(ProductModel::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->attributeMock = $this->createMock(Attribute::class);
        $this->attributeRepositoryMock = $this->createMock(ProductAttributeRepositoryInterface::class);
        $this->attributeAttributeMock = $this->getMockBuilder(ProductAttributeInterface::class)
            ->addMethods(['getSource'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sourceMock = $this->createMock(Table::class);
        $this->peBackendConfigMock = $this->createMock(PeBackendConfig::class);
        $this->canvaBakendConfigMock = $this->createMock(CanvaBackendConfig::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->productAttributeSearchResultsInterfaceMock = $this->createMock(ProductAttributeSearchResultsInterface::class);
        $this->sortOrderBuilderMock = $this->createMock(SortOrderBuilder::class);
        $this->sortOrderMock = $this->createMock(SortOrder::class);
        $this->stockItemMock = $this->createMock(StockItemInterface::class);
        $this->attributeInterfaceMock = $this->createMock(AttributeInterface::class);

        $this->objectManager = new ObjectManager($this);
        $this->attributesMock = $this->objectManager->getObject(Attributes::class,
            [
                'context' => $this->contextMock,
                'urlEncoder' => $this->urlEncoderMock,
                'jsonEncoder' => $this->jsonEncoderMock,
                'string' => $this->stringMock,
                'productHelper' => $this->productHelperMock,
                'productTypeConfig' => $this->productTypeConfigMock,
                'localeFormat' => $this->localeFormatMock,
                'customerSession' => $this->customerSessionMock,
                'productRepository' => $this->productRepositoryMock,
                'priceCurrency' => $this->priceCurrencyMock,
                'attributeRepository' => $this->attributeRepositoryMock,
                'peBackendConfig' => $this->peBackendConfigMock,
                'canvaBakendConfig' => $this->canvaBakendConfigMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'sortOrderBuilder' => $this->sortOrderBuilderMock,
                'data' => [],
            ]
        );
    }

    public function testGetVisibleAttributes()
    {
        $this->productMock->expects($this->atMost(2))->method('getData')->withConsecutive(['visible_attributes'], ['test'])->willReturn('1,2,3', '1');
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->sortOrderBuilderMock->expects($this->once())->method('setField')->with('position')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('create')->willReturn($this->sortOrderMock);

        $this->searchCriteriaBuilderMock->expects($this->atMost(2))->method('addFilter')
            ->withConsecutive(['attribute_code', [1,2,3], 'in'], ['frontend_input', 'multiselect'])->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addSortOrder')->with($this->sortOrderMock)->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($this->searchCriteriaMock);

        $this->attributeAttributeMock->expects($this->once())->method('getAttributeCode')->willReturn('test');
        $this->sourceMock->expects($this->once())->method('getSpecificOptions')->with('1', false)->willReturn([['label' => '1', 'value' => '1']]);
        $this->attributeAttributeMock->expects($this->once())->method('getSource')->willReturn($this->sourceMock);
        $this->attributeAttributeMock->expects($this->once())->method('getDefaultValue')->willReturn('1');
        $this->productAttributeSearchResultsInterfaceMock->expects($this->once())->method('getItems')->willReturn([$this->attributeAttributeMock]);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')->with($this->searchCriteriaMock)->willReturn($this->productAttributeSearchResultsInterfaceMock);

        $this->attributesMock->getVisibleAttributes();
    }

    public function testGetVisibleAttributesNoDefault()
    {
        $this->productMock->expects($this->atMost(2))->method('getData')->withConsecutive(['visible_attributes'], ['test'])->willReturn('1,2,3', '1');
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->sortOrderBuilderMock->expects($this->once())->method('setField')->with('position')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('create')->willReturn($this->sortOrderMock);

        $this->searchCriteriaBuilderMock->expects($this->atMost(2))->method('addFilter')
            ->withConsecutive(['attribute_code', [1,2,3], 'in'], ['frontend_input', 'multiselect'])->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addSortOrder')->with($this->sortOrderMock)->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($this->searchCriteriaMock);

        $this->attributeAttributeMock->expects($this->once())->method('getAttributeCode')->willReturn('test');
        $this->sourceMock->expects($this->once())->method('getSpecificOptions')->with('1', false)->willReturn([['label' => '1', 'value' => '1']]);
        $this->attributeAttributeMock->expects($this->once())->method('getSource')->willReturn($this->sourceMock);
        $this->attributeAttributeMock->expects($this->once())->method('getDefaultValue')->willReturn('2');
        $this->productAttributeSearchResultsInterfaceMock->expects($this->once())->method('getItems')->willReturn([$this->attributeAttributeMock]);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')->with($this->searchCriteriaMock)->willReturn($this->productAttributeSearchResultsInterfaceMock);

        $this->attributesMock->getVisibleAttributes();
    }

    public function testGetVisibleAttributesEmpty()
    {
        $this->productMock->expects($this->once())->method('getData')->with('visible_attributes')->willReturn(false);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->attributesMock->getVisibleAttributes();
    }

    /**
     * @return void
     */
    public function testHasCanvaLink(): void
    {
        $this->attributeInterfaceMock->expects($this->once())->method('getValue')->willReturn('1');
        $this->productMock->expects($this->once())->method('getCustomAttribute')
            ->with(self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN)->willReturn($this->attributeInterfaceMock);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->assertTrue($this->attributesMock->hasCanvaLink());
    }

    /**
     * @return void
     */
    public function testHasCanvaLinkFalse(): void
    {
        $this->attributeInterfaceMock->expects($this->once())->method('getValue')->willReturn('');
        $this->productMock->expects($this->once())->method('getCustomAttribute')
            ->with(self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN)->willReturn($this->attributeInterfaceMock);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->assertFalse($this->attributesMock->hasCanvaLink());
    }

    /**
     * @return void
     */
    public function testHasCanvaLinkNoAttribute(): void
    {
        $this->productMock->expects($this->once())->method('getCustomAttribute')
            ->with(self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN)->willReturn(null);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->assertFalse($this->attributesMock->hasCanvaLink());
    }

    /**
     * @return void
     */
    public function testGetDefaultCanvaLink(): void
    {
        $canvaLink = 'https://canvalink.com';
        $this->canvaBakendConfigMock->expects($this->once())->method('getCanvaLink')
        ->willReturn($canvaLink);

        $canvaLinkResult = $this->attributesMock->getDefaultCanvaLink();
        $this->assertIsString($canvaLinkResult);
        $this->assertEquals($canvaLink, $canvaLinkResult);
    }

    /**
     * @return void
     */
    public function testGetProductEngineUrl(): void
    {
        $peUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts';
        $this->peBackendConfigMock->expects($this->once())->method('getProductEngineUrl')
            ->willReturn($peUrl);

        $peUrlResult = $this->attributesMock->getProductEngineUrl();
        $this->assertIsString($peUrlResult);
        $this->assertEquals($peUrl, $peUrlResult);
    }

    /**
     * @return void
     */
    public function testGetMinSaleQty(): void
    {
        $productId = 1;
        $websiteId = 2;
        $minSaleQty = 5.0;

        $this->productMock->expects($this->once())->method('getId')->willReturn($productId);
        $this->productMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->stockItemMock->expects($this->once())->method('getMinSaleQty')->willReturn($minSaleQty);
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId, $websiteId)->willReturn($this->stockItemMock);

        $this->assertEquals($minSaleQty, $this->attributesMock->getMinSaleQty($this->productMock));
    }

    /**
     * @return void
     */
    public function testGetMaxSaleQty(): void
    {
        $productId = 1;
        $websiteId = 2;
        $maxSaleQty = 50.0;

        $this->productMock->expects($this->once())->method('getId')->willReturn($productId);
        $this->productMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->registryMock->expects($this->atMost(2))->method('registry')->with('product')->willReturn($this->productMock);

        $this->stockItemMock->expects($this->once())->method('getMaxSaleQty')->willReturn($maxSaleQty);
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId, $websiteId)->willReturn($this->stockItemMock);

        $this->assertEquals($maxSaleQty, $this->attributesMock->getMaxSaleQty($this->productMock));
    }

    /**
     * @return void
     */
    public function testGetQuantityAttributeCode(): void
    {
        $result = $this->attributesMock->getQuantityAttributeCode();
        $this->assertIsString($result);
        $this->assertEquals(self::QUANTITY_ATTRIBUTE, $result);
    }

    /**
     * @return void
     */
    public function testGetTextboxOptionLabel(): void
    {
        $result = $this->attributesMock->getTextboxOptionLabel();
        $this->assertIsString($result);
        $this->assertEquals(self::TEXTBOX_ATTRIBUTE_OPTION, $result);
    }

}
