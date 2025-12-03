<?php
/**
 * @category  Fedex
 * @package   Fedex_AllPrintProducts
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Test\Unit\Block;

use Fedex\AllPrintProducts\Block\AllPrintProducts;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelperData;
use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\CategoryRepository;

class AllPrintProductsTest extends TestCase
{
    private Context $contextMock;
    private CollectionFactory $productCollectionFactoryMock;
    private StoreManagerInterface $storeManagerMock;
    private ScopeConfigInterface $scopeConfigMock;
    private AllPrintProducts $allPrintProducts;
    private ToggleHelperData $toggleHelperData;
    private MarketplaceProduct $marketplaceProduct;
    private Resolver $layerResolver;
    private CategoryRepository $categoryRepository;

    /**
     * @return void
     * @group allPrintProducts
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->productCollectionFactoryMock = $this->createMock(CollectionFactory::class);

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->contextMock->expects($this->any())->method('getStoreManager')->willReturn($this->storeManagerMock);

        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->contextMock->expects($this->any())->method('getScopeConfig')->willReturn($this->scopeConfigMock);

        $this->toggleHelperData = $this->createMock(ToggleHelperData::class);
        $this->marketplaceProduct = $this->createMock(MarketplaceProduct::class);
        $this->layerResolver = $this->createMock(Resolver::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $this->allPrintProducts = new AllPrintProducts(
            $this->toggleHelperData,
            $this->marketplaceProduct,
            $this->layerResolver,
            $this->contextMock,
            $this->categoryRepository,
            []
        );
    }

    /**
     * @return void
     * @group allPrintProducts
     */
    public function testGetHeading()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('allprintproducts/general/heading', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn('Sample Heading');

        $this->assertEquals('Sample Heading', $this->allPrintProducts->getHeading());
    }

    /**
     * @return void
     * @group allPrintProducts
     */
    public function testGetSubHeading()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('allprintproducts/general/sub_heading', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn('Sample Sub Heading');

        $this->assertEquals('Sample Sub Heading', $this->allPrintProducts->getSubHeading());
    }
}

