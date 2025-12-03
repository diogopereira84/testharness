<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Plugin\Mirakl\Bulk;
use Fedex\Catalog\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\SharedCatalog\Model\ProductManagement;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Fedex\MarketplaceToggle\Helper\Config as StoreConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Magento\Framework\DB\Adapter\AdapterInterface;
use \Magento\Framework\DB\Select;
use Magento\SharedCatalog\Model\State as SharedCatalogState;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class BulkTest extends TestCase
{
    private ToggleConfig $toggleConfig;
    private ProductRepository $productRepository;
    private ProductManagement $sharedCatalog;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private SharedCatalogRepositoryInterface $sharedCatalogRepository;
    private ResourceConnection $resourceConnection;
    private Config $catalogConfig;

    private AdapterInterface $dbAdapterMock;
    private Select $selectMock;
    private Bulk $bulk;
    private SharedCatalogState $sharedCatalogState;
    private SharedCatalogManagement $sharedCatalogManagement;

    private MarketplaceCheckoutHelper $marketplaceCheckoutHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->sharedCatalog = $this->createMock(ProductManagement::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->sharedCatalogRepository = $this->createMock(SharedCatalogRepositoryInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->catalogConfig = $this->createMock(Config::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);

        $this->dbAdapterMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->selectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->sharedCatalogState = $this->createMock(SharedCatalogState::class);

        $this->sharedCatalogManagement = $this->createMock(SharedCatalogManagement::class);

        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);

        $this->resourceConnection->method('getConnection')->willReturn($this->dbAdapterMock);
        $this->dbAdapterMock->method('select')->willReturn($this->selectMock);
        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('joinInner')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();

        $websiteMock = $this->createConfiguredMock(\Magento\Store\Api\Data\WebsiteInterface::class, [
            'getId' => 1,
        ]);

        $this->sharedCatalogState->method('getActiveWebsites')
            ->willReturn([$websiteMock]);

        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $attributeSetMock->method('getId')->willReturn(777);

        $attributeSetCollectionMock = $this->createMock(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class);
        $attributeSetCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $attributeSetCollectionMock->method('getFirstItem')->willReturn($attributeSetMock);

        $this->bulk = new Bulk(
            $this->toggleConfig,
            $this->productRepository,
            $this->sharedCatalog,
            $this->searchCriteriaBuilder,
            $this->sharedCatalogRepository,
            $this->resourceConnection,
            $this->catalogConfig,
            $this->sharedCatalogState,
            $this->sharedCatalogManagement,
            $this->marketplaceCheckoutHelper
        );
    }

    /**
     * Test beforeImport() function.
     *
     * @return void
     */
    public function testBeforeImport(): void
    {
        $subject = $this->createMock(\Mirakl\Mcm\Model\Product\Import\Adapter\Bulk::class);
        $data = [];

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('fedex/marketplace_configuration/external_product_id')
            ->willReturn('123');

        $this->catalogConfig->expects($this->once())
            ->method('getTigerDisplayUnitCost3P1PProducts')
            ->willReturn(true);

        $this->dbAdapterMock->expects($this->once())
            ->method('fetchOne')
            ->with($this->selectMock)
            ->willReturn(0);

        $sharedCatalogMock = $this->createConfiguredMock(
            \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class,
            ['getId' => 999]
        );
        $this->sharedCatalogManagement->method('getPublicCatalog')->willReturn($sharedCatalogMock);

        $result = $this->bulk->beforeImport($subject, $data);

        $expectedData = [
            'page_layout' => StoreConfig::MIRAKL_LAYOUT_IDENTIFIER,
            'is_catalog_product' => 1,
            'product_id' => '123',
            'in_store_pickup' => 0,
            'is_delivery_only' => 1,
            'shared_catalogs' => '999',
        ];

        $this->assertEquals([$expectedData], $result);
    }

    /**
     * Test getDefaultOptionIdForInStorePickup() function.
     *
     * @return void
     */
    public function testGetDefaultOptionIdForInStorePickup(): void
    {
        $this->dbAdapterMock->expects($this->once())
            ->method('fetchOne')
            ->with($this->selectMock)
            ->willReturn(123);

        $result = $this->bulk->getDefaultOptionIdForInStorePickup();
        $this->assertEquals(123, $result);
    }

    /**
     * Test getCustomizableNoOptionId() function.
     *
     * @return void
     */
    public function testGetCustomizableNoOptionId(): void
    {
        $this->dbAdapterMock->expects($this->once())
            ->method('fetchOne')
            ->with($this->selectMock)
            ->willReturn(456);

        $result = $this->bulk->getCustomizableNoOptionId();
        $this->assertEquals(456, $result);
    }
}
