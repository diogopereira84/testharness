<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Observer;

use Fedex\MarketplaceProduct\Observer\UpdateMktProductCategoriesObserver;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\TestCase;

class UpdateMktProductCategoriesObserverTest extends TestCase
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->categoryLinkManagement = $this->createMock(CategoryLinkManagementInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['getChangedPaths', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test execute method without epro config.
     *
     * @return void
     */
    public function testExecuteWithoutEproPrintConfigPath(): void
    {
        $configPath = ['other_config_path'];
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->observer);
        $this->observer->expects($this->once())
            ->method('getChangedPaths')
            ->willReturn($configPath);

        $this->resourceConnection->expects($this->never())
            ->method('getConnection');

        $this->categoryRepository->expects($this->never())
            ->method('get');

        $this->categoryLinkManagement->expects($this->never())
            ->method('assignProductToCategories');

        $observer = new UpdateMktProductCategoriesObserver(
            $this->moduleDataSetup,
            $this->categoryLinkManagement,
            $this->categoryRepository,
            $this->resourceConnection,
            $this->scopeConfig
        );
        $observer->execute($this->observer);
    }

    /**
     * Test execute method with epro config.
     *
     * @return void
     */
    public function testGetProductCategoryIds()
    {
        $productId = 1;
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->setMethods(['from', 'where', 'fetchAll'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnection->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $tableName = 'catalog_category_product';
        $this->resourceConnection->expects($this->once())
            ->method('getTableName')
            ->with($tableName)
            ->willReturn($tableName);

        $categories = [
            ['category_id' => 1],
            ['category_id' => 2]
        ];
        $connection->expects($this->once())
            ->method('select')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('from')
            ->with($tableName, ['category_id'])
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('where')
            ->with('product_id = ?', $productId)
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('fetchAll')
            ->willReturn($categories);

        $observer = new UpdateMktProductCategoriesObserver(
            $this->moduleDataSetup,
            $this->categoryLinkManagement,
            $this->categoryRepository,
            $this->resourceConnection,
            $this->scopeConfig
        );
        $result = $observer->getProductCategoryIds($productId);

        $this->assertEquals([1, 2], $result);
    }
}
